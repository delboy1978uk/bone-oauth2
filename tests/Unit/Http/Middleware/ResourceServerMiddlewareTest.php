<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Http\Middleware\ResourceServerMiddleware;
use Bone\OAuth2\Repository\ClientRepository;
use Bone\OAuth2\Service\ClientService;
use Codeception\Test\Unit;
use Del\Entity\User;
use Del\Service\UserService;
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
    private UserService $userService;
    private ClientService $clientService;

    protected function _before()
    {
        $user = $this->createMock(User::class);
        $client = $this->createMock(Client::class);
        $client->method('getUser')->willReturn($user);
        $clientRepo = $this->createMock(ClientRepository::class);
        $clientRepo->method('findOneBy')->willReturn($client);
        $this->server = $this->createMock(ResourceServer::class);
        $this->userService = $this->createMock(UserService::class);
        $this->clientService = $this->createMock(ClientService::class);
        $this->clientService->method('getClientRepository')->willReturn($clientRepo);
        $this->middleware = new ResourceServerMiddleware($this->server, $this->userService, $this->clientService);
    }

    public function testProcessWithValidToken()
    {
        $request = new ServerRequest();
        $validatedRequest = new ServerRequest();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = new Response();

        $this->server->expects($this->once())
            ->method('validateAuthenticatedRequest')
            ->willReturn($validatedRequest);

        $handler->expects($this->once())
            ->method('handle')
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
