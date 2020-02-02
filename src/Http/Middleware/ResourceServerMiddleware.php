<?php

namespace Bone\OAuth2\Http\Middleware;

use Del\Service\UserService;
use Exception;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\JsonResponse;

class ResourceServerMiddleware implements MiddlewareInterface
{
    /**
     * @var ResourceServer $leagueMiddleware
     */
    private $resourceServer;

    /** @var ResponseFactoryInterface $responseFactory */
    private $responseFactory;

    /** @var UserService $userService */
    private $userService;

    /**
     * ResourceServer constructor.
     * @param ResourceServer $resourceServer
     */
    public function __construct(ResourceServer $resourceServer, UserService $userService, ResponseFactoryInterface $responseFactory)
    {
        $this->resourceServer = $resourceServer;
        $this->responseFactory = $responseFactory;
        $this->userService = $userService;
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
            $userId = $request->getAttribute('oauth_user_id');

            if ($userId) {
                $user = $this->userService->findUserById($userId);
                $request = $request->withAttribute('user', $user);
            }

            return $handler->handle($request);

        } catch (OAuthServerException $e) {
            $status = $e->getHttpStatusCode();
        } catch (Exception $e) {
            $code = $e->getCode();
            $status = ($code > 399 && $code < 600) ? $e->getCode() : 500;
        }

        return new JsonResponse([
            'error' => $e->getMessage(),
        ], $status);
    }
}