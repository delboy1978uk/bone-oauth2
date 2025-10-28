<?php

declare(strict_types=1);

namespace Bone\OAuth2\Http\Middleware;

use Bone\Http\Response\Json\Error\ErrorResponse;
use Del\Service\UserService;
use Exception;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\JsonResponse;

class ResourceServerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ResourceServer $resourceServer,
        private UserService $userService
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $request = $this->resourceServer->validateAuthenticatedRequest($request);
            $userId = (int) $request->getAttribute('oauth_user_id');

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

        return new ErrorResponse($e->getMessage(), $status);
    }
}
