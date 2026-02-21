<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use Bone\OAuth2\Http\Middleware\ResourceServerMiddleware;
use Codeception\Test\Unit;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use League\OAuth2\Server\ResourceServer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Del\Service\UserService;
use Bone\OAuth2\Service\ClientService;
use Del\Entity\User;

class ResourceServerMiddlewareUncoveredTest extends Unit
{
    public function testProcessWithAuthenticatedUser()
    {
        $resourceServer = $this->createMock(ResourceServer::class);
        $userService = $this->createMock(UserService::class);
        $clientService = $this->createMock(ClientService::class);

        $middleware = new ResourceServerMiddleware(
            $resourceServer,
            $userService,
            $clientService
        );

        $request = new ServerRequest();
        $request = $request->withAttribute('oauth_user_id', '123');
        $request = $request->withHeader('Authorization', 'Bearer valid-token');

        // Mock validateAuthenticatedRequest to return the request with attributes
        $resourceServer->expects($this->once())
            ->method('validateAuthenticatedRequest')
            ->willReturn($request);

        // Expect UserService to be called
        $user = new User();
        $userService->expects($this->once())
            ->method('findUserById')
            ->with(123)
            ->willReturn($user);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function ($req) use ($user) {
                return $req->getAttribute('user') === $user;
            }))
            ->willReturn(new Response());

        $response = $middleware->process($request, $handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testProcessWithGenericExceptionWithValidStatusCode()
    {
        $resourceServer = $this->createMock(ResourceServer::class);
        $userService = $this->createMock(UserService::class);
        $clientService = $this->createMock(ClientService::class);

        $middleware = new ResourceServerMiddleware(
            $resourceServer,
            $userService,
            $clientService
        );

        $request = new ServerRequest();
        $request = $request->withHeader('Authorization', 'Bearer token');

        $handler = $this->createMock(RequestHandlerInterface::class);

        // Mock validateAuthenticatedRequest to throw generic exception with code 418
        $resourceServer->expects($this->once())
            ->method('validateAuthenticatedRequest')
            ->willThrowException(new \Exception('Teapot', 418));

        $response = $middleware->process($request, $handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(418, $response->getStatusCode());
    }
}
