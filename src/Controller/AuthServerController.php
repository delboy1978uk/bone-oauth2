<?php

namespace Bone\OAuth2\Controller;

use Exception;
use Bone\Controller\Controller;
use Bone\OAuth2\Entity\OAuthUser;
use Bone\OAuth2\Service\PermissionService;
use Bone\Server\SessionAwareInterface;
use Bone\Server\Traits\HasSessionTrait;
use Del\Service\UserService;
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

    /** @var AuthorizationServer $server */
    private $server;

    /** @var UserService $userService */
    private $userService;

    /** @var PermissionService $userService */
    private $permissionService;

    /**
     * AuthServerController constructor.
     * @param AuthorizationServer $server
     */
    public function __construct(AuthorizationServer $server, UserService $userService, PermissionService $permissionService)
    {
        $this->server = $server;
        $this->userService = $userService;
        $this->permissionService = $permissionService;
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
     * @param ServerRequestInterface $request
     * @param array $args
     * @return ResponseInterface
     */
    public function authorizeAction(ServerRequestInterface $request, array $args): ResponseInterface
    {
        /* @var AuthorizationServer $server */
        $server = $this->server;
        $response = new Response();

        try {
            // Validate the HTTP request and return an AuthorizationRequest object.
            // The auth request object can be serialized into a user's session
            $authRequest = $server->validateAuthorizationRequest($request);
            $userId = $this->getSession()->get('user');
            /** @var OAuthUser $user */
            $user = $this->userService->findUserById($userId);
            $authRequest->setUser($user);
            $scopes = $authRequest->getScopes();
            $client = $authRequest->getClient();
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

            // Once the user has approved or denied the client update the status
            // (true = approved, false = denied)
            // Return the HTTP redirect response
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
     *     @OA\Response(response="200", description="An access token"),
     *     tags={"auth"},
     *     @OA\Parameter(
     *         name="grant_type",
     *         in="formData",
     *         description="the type of grant",
     *         required=true,
     *         @OA\Schema(type="string", default="client_credentials")
     *     ),
     *     @OA\Parameter(
     *         name="client_id",
     *         in="formData",
     *         description="the client id",
     *         required=true,
     *         @OA\Schema(type="string", default="0123456789abcdef")
     *     ),
     *     @OA\Parameter(
     *         name="client_secret",
     *         in="formData",
     *         description="the client secret",
     *         required=false,
     *         @OA\Schema(type="string", default="0123456789abcdef")
     *     ),
     *     @OA\Parameter(
     *         name="scope",
     *         in="formData",
     *         description="the scopes you wish to use",
     *         required=false,
     *         @OA\Schema(type="string", default="basic")
     *     ),
     *     @OA\Parameter(
     *         name="redirect_uri",
     *         in="formData",
     *         description="with the same redirect URI the user was redirect back to",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="code",
     *         in="formData",
     *         description="with the authorization code from the query string",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     * )
     *
     * @param ServerRequestInterface $request
     * @param array $args
     * @return ResponseInterface
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
            ]);
        } catch (Exception $e) {
            $response = new JsonResponse([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }


        return $response;
    }
}