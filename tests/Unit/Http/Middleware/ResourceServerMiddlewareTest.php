<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use Bone\OAuth2\Http\Middleware\ResourceServerMiddleware;
use Codeception\Test\Unit;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ResourceServerMiddlewareTest extends Unit
{
    private ResourceServerMiddleware $middleware;
    private ResourceServer $server;

    protected function _before()
    {
        $this->server = $this->createMock(ResourceServer::class);
        $this->middleware = new ResourceServerMiddleware($this->server);
    }

    public function testProcessWithValidToken()
    {
        $request = new ServerRequest();
        $validatedRequest = new ServerRequest();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = new Response();

        $this->server->expects($this->once())
            ->method('validateAuthenticatedRequest')
            ->with($request)
            ->willReturn($validatedRequest);

        $handler->expects($this->once())
            ->method('handle')
            ->with($validatedRequest)
            ->willReturn($response);

        $result = $this->middleware->process($request, $handler);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testProcessWithInvalidToken()
    {
        $request = new ServerRequest();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $exception = OAuthServerException::accessDenied('Invalid token');

        $this->server->expects($this->once())
            ->method('validateAuthenticatedRequest')
            ->with($request)
            ->willThrowException($exception);

        $handler->expects($this->never())
            ->method('handle');

        $result = $this->middleware->process($request, $handler);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
