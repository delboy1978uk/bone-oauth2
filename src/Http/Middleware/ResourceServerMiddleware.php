<?php

namespace Bone\OAuth2\Http\Middleware;

use Bone\Server\SessionAwareInterface;
use Bone\Traits\HasSessionTrait;
use Del\Exception\UserException;
use Del\Service\UserService;
use Del\SessionManager;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ResourceServerMiddleware implements MiddlewareInterface
{
    /**
     * @var ResourceServer $leagueMiddleware
     */
    private $resourceServer;

    /** @var ResponseFactoryInterface $responseFactory */
    private $responseFactory;

    /**
     * ResourceServer constructor.
     * @param ResourceServer $resourceServer
     */
    public function __construct(ResourceServer $resourceServer, ResponseFactoryInterface $responseFactory)
    {
        $this->resourceServer = $resourceServer;
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
            $request = $this->resourceServer->validateAuthenticatedRequest($request);
        } catch (OAuthServerException $exception) {
            return $exception->generateHttpResponse($this->responseFactory->createResponse());
            // @codeCoverageIgnoreStart
        } catch (\Exception $exception) {
            return (new OAuthServerException($exception->getMessage(), 0, 'unknown_error', 500))
                ->generateHttpResponse($this->responseFactory->createResponse());
            // @codeCoverageIgnoreEnd
        }

        // Pass the request on to the next responder in the chain
        return $handler->handle($request);
    }
}