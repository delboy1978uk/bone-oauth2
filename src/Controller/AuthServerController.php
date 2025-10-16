<?php

declare(strict_types=1);

namespace Bone\OAuth2\Controller;

use Bone\OAuth2\Entity\Client;
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
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;

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
            /** @var OAuthUser $user */
            $user = $this->userService->findUserById($userId);
            $cotinueAsUser = $request->getQueryParams()['continue'] ?? false;

            if (!$request->hasHeader('X_BONE_USER_ACTIVATE')) {
                if ($request->hasHeader('Referer') === false && $session->has('authRequest') === false && $cotinueAsUser === false) {
                    $request = $request->withAttribute('user', null);
                    $session->set('authRequest', \serialize($request));
                    $body = $this->getView()->render('boneoauth2::continue', [
                        'user' => $user,
                    ]);

                    return new HtmlResponse($body);
                }

                if ($request->hasHeader('Referer') && \str_contains($request->getHeader('Referer')[0], 'user/login')) {
                    $request = $request->withAttribute('user', null);
                    $session->set('authRequest', \serialize($request));
                }

                $request = \unserialize($session->get('authRequest'));
                $session->unset('authRequest');
            }

            $authRequest = $server->validateAuthorizationRequest($request);
            $authRequest->setUser($user);
            $client = $authRequest->getClient();

            if (!$client->isProprietary()) {
                $scopes = $authRequest->getScopes();
                $userScopes = $this->permissionService->getScopes($user, $client);
                $missingScopes = array_diff_key($scopes, $userScopes);
                $approvedCount = count($scopes) - count($missingScopes);

                if (count($missingScopes) && $request->getMethod() === 'GET') {
                    $body = $this->getView()->render('boneoauth2::authorize', [
                        'scopes' => $scopes,
                        'approvedCount' => $approvedCount,
                        'missingScopes' => $missingScopes,
                        'client' => $client,
                        'user' => $user,
                    ]);

                    return new HtmlResponse($body);
                }

                if (count($missingScopes) && $request->getMethod() === 'POST') {
                    $this->permissionService->addScopes($user, $client, $missingScopes);
                }
            }

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

        $redirectUri = $response->getHeader('Location');

        if (!empty($redirectUri)) {

            if ($redirectUri[0][0] === '?') {
                $uri = \str_replace('?', '', $redirectUri[0]);
                \parse_str($uri, $vars);
                $response = new JsonResponse($vars);
            }
        }

        return $response;
    }

    public function accessTokenAction(ServerRequestInterface $request, array $args): ResponseInterface
    {
        /* @var AuthorizationServer $server */
        $server = $this->server;
        $response = new JsonResponse([]);

        try {
            $response = $server->respondToAccessTokenRequest($request, $response);
            $response->getBody()->rewind(); // Insane that we have to do this haha!
        } catch (OAuthServerException $e) {
            $response = new JsonResponse([
                'error' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ], 400);
        } catch (Exception $e) {
            $response = new JsonResponse([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
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
