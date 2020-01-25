<?php

namespace Bone\OAuth2\Http\Middleware\OAuth2;

use Exception;
use Bone\Server\SessionAwareInterface;
use Bone\Traits\HasSessionTrait;
use Del\Exception\UserException;
use Del\Service\UserService;
use Del\SessionManager;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthServerMiddleware implements MiddlewareInterface
{
    /**
     * @var AuthorizationServer
     */
    private $server;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @param AuthorizationServer $server
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(AuthorizationServer $server, ResponseFactoryInterface $responseFactory)
    {
        $this->server = $server;
        $this->responseFactory = $responseFactory;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $response = $this->server->respondToAccessTokenRequest($request, $this->responseFactory->createResponse());
        } catch (OAuthServerException $exception) {
            return $exception->generateHttpResponse($this->responseFactory->createResponse());
            // @codeCoverageIgnoreStart
        } catch (Exception $exception) {
            return (new OAuthServerException($exception->getMessage(), 0, 'unknown_error', 500))
                ->generateHttpResponse($this->responseFactory->createResponse());
            // @codeCoverageIgnoreEnd
        }

        // Pass the request on to the next responder in the chain
        return $handler->handle($request);
    }
}