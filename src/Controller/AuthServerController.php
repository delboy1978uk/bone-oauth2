<?php

declare(strict_types=1);

namespace Bone\OAuth2\Controller;

use Bone\Http\Response\Json\Error\BadRequestResponse;
use Bone\Http\Response\Json\Error\ServerErrorResponse;
use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\OAuthUser;
use Bone\OAuth2\Form\RegisterClientForm;
use Bone\OAuth2\Service\ClientService;
use Exception;
use Bone\Controller\Controller;
use Del\Entity\User;
use Bone\OAuth2\Service\PermissionService;
use Bone\Server\SessionAwareInterface;
use Bone\Server\Traits\HasSessionTrait;
use Del\Service\UserService;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\JsonResponse;

use function parse_str;
use function str_replace;

class AuthServerController extends Controller implements SessionAwareInterface
{
    use HasSessionTrait;

    public function __construct(
        private AuthorizationServer $server,
        private UserService $userService,
        private PermissionService $permissionService,
        private ClientService $clientService
    ) {
    }

    public function authorizeAction(ServerRequestInterface $request, array $args): ResponseInterface
    {
        /* @var AuthorizationServer $server */
        $server = $this->server;
        $response = new Response();
        $session = $this->getSession();

        try {
            $userId = $session->get('user');

            if (!$userId) { 
                return new RedirectResponse('/login'); 
            }

            $user = $this->userService->findUserById($userId);
            $authRequest = $server->validateAuthorizationRequest($request);
            $oauthUser = OAuthUser::createFromBaseUser($user);
            $authRequest->setUser($oauthUser);
            $client = $authRequest->getClient();
            $authRequest->setAuthorizationApproved(true);
            $response = $server->completeAuthorizationRequest($authRequest, $response);

        } catch (OAuthServerException $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], $e->getHttpStatusCode());
        } catch (Exception $e) {
            $code = $e->getCode();
            $status = ($code > 399 && $code < 600) ? $code : 500;

            return new JsonResponse([
                'error' => $e->getMessage(),
            ], $status);
        }

        $redirectUri = $response ? $response->getHeader('Location') : null;

        if (!empty($redirectUri)) {

            if ($redirectUri[0][0] === '?') {
                $uri = str_replace('?', '', $redirectUri[0]);
                parse_str($uri, $vars);
                $response = new JsonResponse($vars);
            }
        }

        return $response;
    }

    public function accessTokenAction(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $server = $this->server;
        $response = new JsonResponse([]);

        try {
            $response = $server->respondToAccessTokenRequest($request, $response);
            $response->getBody()->rewind();
        } catch (OAuthServerException $e) {
            $response = new BadRequestResponse($e->getMessage());
        } catch (Exception $e) {
            $response = new ServerErrorResponse([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        return $response;
    }


    public function registerAction(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $post = $request->getParsedBody();
        $post['redirect_uris'] = $post['redirect_uris'];
        $form = new RegisterClientForm('register');
        $form->populate($post);

        return $this->clientService->registerNewClient($form);
    }

    public function loginAsSomeoneElse(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $this->getSession()->unset('user');
        \setcookie('resu', '', 1, '/');
        /** @var ServerRequest $authRequest */
        $authRequest = \unserialize($this->getSession()->get('authRequest'));

        return new RedirectResponse($authRequest->getUri());
    }
}
