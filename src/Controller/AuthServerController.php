<?php

declare(strict_types=1);

namespace Bone\OAuth2\Controller;

use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Form\RegisterClientForm;
use Bone\OAuth2\Service\ClientService;
use Exception;
use Bone\Controller\Controller;
use Bone\OAuth2\Entity\OAuthUser;
use Bone\OAuth2\Service\PermissionService;
use Bone\Server\SessionAwareInterface;
use Bone\Server\Traits\HasSessionTrait;
use Del\Service\UserService;
use Laminas\Diactoros\Response\RedirectResponse;
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

    /**
     * Authorize a client via OAuth2
     * @OA\Get(
     *     path="/oauth2/authorize",
     *     @OA\Response(response="200", description="An access token"),
     *     tags={"auth"},
     *     @OA\Parameter(
     *         name="response_type",
     *         in="query",
     *         description="the type of response",
     *         required=true,
     *         @OA\Schema(type="string", default="code")
     *     ),
     *     @OA\Parameter(
     *         name="client_id",
     *         in="query",
     *         description="the client identifier",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="client_secret",
     *         in="query",
     *         description="the client identifier",
     *         required=false,
     *         @OA\Schema(type="string", default="testclient")
     *     ),
     *     @OA\Parameter(
     *         name="redirect_uri",
     *         in="query",
     *         description="where to send the response",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="state",
     *         in="query",
     *         description="with a CSRF token. This parameter is optional but highly recommended.",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="scope",
     *         in="query",
     *         description="allowed scopes, space separated",
     *         required=false,
     *         @OA\Schema(type="string", default="basic")
     *     )
     * )
     */
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

            if ($session->has('authRequest') === false && $cotinueAsUser === false) {
                $session->set('authRequest', \serialize($request));
                $body = $this->getView()->render('boneoauth2::continue', [
                    'user' => $user,
                ]);

                return new HtmlResponse($body);
            }

            $request = \unserialize($session->get('authRequest'));
            $session->unset('authRequest');
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

    /**
     * Fetch an OAuth2 access token
     * @OA\Post(
     *     path="/oauth2/token",
     *     operationId="accessToken",
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 required={"grant_type", "client_id"},
     *                 @OA\Property(
     *                     property="grant_type",
     *                     type="string",
     *                     default="client_credentials",
     *                     description="the type of grant"
     *                 ),
     *                 @OA\Property(
     *                     property="client_id",
     *                     type="string",
     *                     description="the client id"
     *                 ),
     *                 @OA\Property(
     *                     property="client_secret",
     *                     type="string",
     *                     description="the client secret"
     *                 ),
     *                 @OA\Property(
     *                     property="scope",
     *                     type="string",
     *                     description="the scopes you wish to use"
     *                 ),
     *                 @OA\Property(
     *                     property="redirect_uri",
     *                     type="string",
     *                     description="the redirect url for post authorization"
     *                 ),
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                     description="with the authorization code from the query string"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response="200", description="An access token"),
     *     tags={"auth"}
     * )
     *
     */
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

    /**
     * Register an OAuth2 Client
     * @OA\Post(
     *     path="/oauth2/register",
     *     operationId="registerClient",
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"redirect_uris", "client_id", "token_endpoint_auth_method", "logo_uri"},
     *                 @OA\Property(
     *                     property="redirect_uris",
     *                     type="array",
     *                     description="an array of redirect uri's (only one supported at present)",
     *                     @OA\Items(type="string", example="http://fake/callback")
     *                 ),
     *                 @OA\Property(
     *                     property="client_name",
     *                     description="the client name",
     *                     type="string",
     *                     example="Test Client"
     *                 ),
     *                 @OA\Property(
     *                     property="token_endpoint_auth_method",
     *                     description="none, client_secret_post, or client_secret_basic",
     *                     type="string",
     *                     example="client_secret_basic"
     *                 ),
     *                 @OA\Property(
     *                     property="logo_uri",
     *                     description="the application's logo",
     *                     type="string",
     *                     example="https://fake/image.jpg"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response="200", description="An access token"),
     *     tags={"auth"},
     *     security={
     *         {"oauth2": {"register"}}
     *     }
     * )
     * @todo right now we only create auth_code clients via this endpoint, check token_endpoint_auth_method
     */
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
        /** @var ServerRequestInterface $authRequest */
        $authRequest = \unserialize($this->getSession()->get('authRequest'), ['allowed_classes' => ServerRequestInterface::class]);

        return new RedirectResponse($authRequest->getUri());
    }
}
