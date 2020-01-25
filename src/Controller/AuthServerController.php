<?php

namespace Bone\OAuth2\Controller;

use Bone\Exception;
use Bone\OAuth2\Entity\OAuthUser;
use Del\Entity\User;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Stream;

class AuthServerController
{
    /** @var AuthorizationServer $server */
    private $server;

    /**
     * AuthServerController constructor.
     * @param AuthorizationServer $server
     */
    public function __construct(AuthorizationServer $server)
    {
        $this->server = $server;
    }

    /**
     *
     * @OA\Get(
     *     path="/oauth2/authorize",
     *     @OA\Response(response="200", description="An access token"),
     *     tags={"auth"},
     *     @OA\Parameter(
     *         name="response_type",
     *         in="query",
     *         type="string",
     *         description="the type of response",
     *         required=true,
     *         default="code"
     *     ),
     *     @OA\Parameter(
     *         name="client_id",
     *         in="query",
     *         type="string",
     *         description="the client identifier",
     *         required=true,
     *         default="testclient"
     *     ),
     *     @OA\Parameter(
     *         name="client_secret",
     *         in="query",
     *         type="string",
     *         description="the client identifier",
     *         required=false,
     *         default="testclient"
     *     ),
     *     @OA\Parameter(
     *         name="redirect_uri",
     *         in="query",
     *         type="string",
     *         description="where to send the response",
     *         required=false
     *     ),
     *     @OA\Parameter(
     *         name="state",
     *         in="query",
     *         type="string",
     *         description="with a CSRF token. This parameter is optional but highly recommended.",
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="scope",
     *         in="query",
     *         type="string",
     *         description="allowed scopes, space separated",
     *         required=false,
     *     )
     * )
     */
    /**
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
            // Once the user has logged in set the user on the AuthorizationRequest
            $authRequest->setUser(new OAuthUser());
            // Once the user has approved or denied the client update the status
            // (true = approved, false = denied)
            $authRequest->setAuthorizationApproved(true);
            // Return the HTTP redirect response
            $response = $server->completeAuthorizationRequest($authRequest, $response);

        } catch (OAuthServerException $e) {
            $response = $e->generateHttpResponse($response);

        } catch (Exception $e) {
            $body = new Stream('php://temp', 'r+');
            $body->write($e->getMessage());
            $response = $response->withStatus(500)->withBody($body);
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
     * @OA\Post(
     *     path="/oauth2/access-token",
     *     operationId="accessToken",
     *     @OA\Response(response="200", description="An access token"),
     *     tags={"auth"},
     *     @OA\Parameter(
     *         name="grant_type",
     *         in="formData",
     *         type="string",
     *         description="the type of grant",
     *         required=true,
     *         default="client_credentials",
     *     ),
     *     @OA\Parameter(
     *         name="client_id",
     *         in="formData",
     *         type="string",
     *         description="the client id",
     *         required=true,
     *         default="ceac682a9a4808bf910ad49134230e0e"
     *     ),
     *     @OA\Parameter(
     *         name="client_secret",
     *         in="formData",
     *         type="string",
     *         description="the client secret",
     *         required=false,
     *         default="JDJ5JDEwJGNEd1J1VEdOY0YxS3QvL0pWQzMxay52"
     *     ),
     *     @OA\Parameter(
     *         name="scope",
     *         in="formData",
     *         type="string",
     *         description="the scopes you wish to use",
     *         required=false,
     *         default="admin"
     *     ),
     *     @OA\Parameter(
     *         name="redirect_uri",
     *         in="formData",
     *         type="string",
     *         description="with the same redirect URI the user was redirect back to",
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="code",
     *         in="formData",
     *         type="string",
     *         description="with the authorization code from the query string",
     *         required=false,
     *     ),
     * )
     */
    public function accessTokenAction(ServerRequestInterface $request, array $args): ResponseInterface
    {
        /* @var AuthorizationServer $server */
        $server = $this->server;
        $response = new Response();

        try {
            $response = $server->respondToAccessTokenRequest($request, $response);
        } catch (OAuthServerException $e) {
            $response = new JsonResponse([
                'error' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);
        }

        return $response;
    }
}