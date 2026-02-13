<?php

namespace Bone\OAuth2\Test\Unit\Http\Middleware;

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
    public function testProcessWithValidToken()
    {
        $resourceServer = $this->createMock(ResourceServer::class);
        $middleware = new ResourceServerMiddleware($resourceServer);
        
        $request = new ServerRequest();
        $request = $request->withHeader('Authorization', 'Bearer valid-token');
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());
        
        // Mock validateAuthenticatedRequest to return the request
        $resourceServer->expects($this->once())
            ->method('validateAuthenticatedRequest')
            ->willReturn($request);
        
        $response = $middleware->process($request, $handler);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testProcessWithInvalidToken()
    {
        $resourceServer = $this->createMock(ResourceServer::class);
        $middleware = new ResourceServerMiddleware($resourceServer);
        
        $request = new ServerRequest();
        $request = $request->withHeader('Authorization', 'Bearer invalid-token');
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        
        // Mock validateAuthenticatedRequest to throw OAuthServerException
        $exception = OAuthServerException::accessDenied('Invalid token');
        $resourceServer->expects($this->once())
            ->method('validateAuthenticatedRequest')
            ->willThrowException($exception);
        
        $response = $middleware->process($request, $handler);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
    }

    public function testProcessWithMissingToken()
    {
        $resourceServer = $this->createMock(ResourceServer::class);
        $middleware = new ResourceServerMiddleware($resourceServer);
        
        $request = new ServerRequest();
        // No Authorization header
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        
        // Mock validateAuthenticatedRequest to throw OAuthServerException
        $exception = OAuthServerException::accessDenied('Missing token');
        $resourceServer->expects($this->once())
            ->method('validateAuthenticatedRequest')
            ->willThrowException($exception);
        
        $response = $middleware->process($request, $handler);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testProcessWithGenericException()
    {
        $resourceServer = $this->createMock(ResourceServer::class);
        $middleware = new ResourceServerMiddleware($resourceServer);
        
        $request = new ServerRequest();
        $request = $request->withHeader('Authorization', 'Bearer token');
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        
        // Mock validateAuthenticatedRequest to throw generic exception
        $resourceServer->expects($this->once())
            ->method('validateAuthenticatedRequest')
            ->willThrowException(new \Exception('Server error'));
        
        $response = $middleware->process($request, $handler);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
    }
}
