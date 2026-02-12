<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use Bone\OAuth2\Exception\OAuthException;
use Bone\OAuth2\Http\Middleware\ScopeCheck;
use Codeception\Test\Unit;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
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
        $handler->expects($this->never())->method('handle');
        $this->expectException(OAuthException::class);
        $this->middleware->process($request, $handler);
    }

    public function testProcessWithNoScopes()
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('oauth_scopes', []);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');
        $this->expectException(OAuthException::class);
        $this->middleware->process($request, $handler);
    }
}
