<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use Bone\OAuth2\Http\Middleware\ScopeCheck;
use Codeception\Test\Unit;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ScopeCheckTest extends Unit
{
    private ScopeCheck $middleware;

    protected function _before()
    {
        $this->middleware = new ScopeCheck(['read', 'write']);
    }

    public function testProcessWithRequiredScopes()
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('oauth_scopes', ['read', 'write', 'admin']);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = new Response();

        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $result = $this->middleware->process($request, $handler);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testProcessWithMissingScopes()
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('oauth_scopes', ['read']);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $handler->expects($this->never())
            ->method('handle');

        $result = $this->middleware->process($request, $handler);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testProcessWithNoScopes()
    {
        $request = new ServerRequest();
        $handler = $this->createMock(RequestHandlerInterface::class);

        $handler->expects($this->never())
            ->method('handle');

        $result = $this->middleware->process($request, $handler);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
