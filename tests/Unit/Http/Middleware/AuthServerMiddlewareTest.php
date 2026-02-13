<?php

namespace Bone\OAuth2\Test\Unit\Http\Middleware;

use Bone\OAuth2\Http\Middleware\AuthServerMiddleware;
use Codeception\Test\Unit;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthServerMiddlewareTest extends Unit
{
    public function testProcessWithValidAuthorizationRequest()
    {
        $authServer = $this->createMock(AuthorizationServer::class);
        $middleware = new AuthServerMiddleware($authServer);
        
        $request = new ServerRequest();
        $request = $request->withMethod('GET');
        $request = $request->withQueryParams([
            'response_type' => 'code',
            'client_id' => 'test-client'
        ]);
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());
        
        // Mock validateAuthorizationRequest to return a valid request
        $authServer->expects($this->once())
            ->method('validateAuthorizationRequest')
            ->willReturn($request);
        
        $response = $middleware->process($request, $handler);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testProcessWithOAuthException()
    {
        $authServer = $this->createMock(AuthorizationServer::class);
        $middleware = new AuthServerMiddleware($authServer);
        
        $request = new ServerRequest();
        $request = $request->withMethod('GET');
        $request = $request->withQueryParams([
            'response_type' => 'code',
            'client_id' => 'invalid-client'
        ]);
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        
        // Mock validateAuthorizationRequest to throw OAuthServerException
        $exception = OAuthServerException::invalidClient($request);
        $authServer->expects($this->once())
            ->method('validateAuthorizationRequest')
            ->willThrowException($exception);
        
        $response = $middleware->process($request, $handler);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
    }

    public function testProcessWithGenericException()
    {
        $authServer = $this->createMock(AuthorizationServer::class);
        $middleware = new AuthServerMiddleware($authServer);
        
        $request = new ServerRequest();
        $request = $request->withMethod('GET');
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        
        // Mock validateAuthorizationRequest to throw generic exception
        $authServer->expects($this->once())
            ->method('validateAuthorizationRequest')
            ->willThrowException(new \Exception('Something went wrong'));
        
        $response = $middleware->process($request, $handler);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testProcessWithPostRequest()
    {
        $authServer = $this->createMock(AuthorizationServer::class);
        $middleware = new AuthServerMiddleware($authServer);
        
        $request = new ServerRequest();
        $request = $request->withMethod('POST');
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());
        
        $response = $middleware->process($request, $handler);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
