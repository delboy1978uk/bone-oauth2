<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Http\Middleware\AuthServerMiddleware;
use Bone\OAuth2\Service\PermissionService;
use Bone\Server\Session;
use Bone\View\ViewEngineInterface;
use Codeception\Test\Unit;
use Del\Entity\User;
use Del\Service\UserService;
use Del\SessionManager;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthServerMiddlewareTest extends Unit
{
    private AuthServerMiddleware $middleware;
    private UserService $userService;
    private SessionManager $session;
    private AuthorizationServer $authorizationServer;
    private PermissionService $permissionService;
    private ViewEngineInterface $viewEngine;

    protected function _before()
    {
        $authRequest = $this->createMock(AuthorizationRequest::class);
        $client = new Client();
        $client->setProprietary(true);
        $authRequest->method('getClient')->willReturn($client);
        $this->userService = $this->createMock(UserService::class);
        $this->authorizationServer = $this->createMock(AuthorizationServer::class);
        $this->authorizationServer->method('validateAuthorizationRequest')->willReturn($authRequest);
        $this->userService = $this->createMock(UserService::class);
        $this->permissionService = $this->createMock(PermissionService::class);
        $this->viewEngine = $this->createMock(ViewEngineInterface::class);
        $this->viewEngine->method('render')->willReturn('test');
        $this->session = SessionManager::getInstance();
        $this->middleware = new AuthServerMiddleware($this->userService, $this->viewEngine, $this->session, $this->authorizationServer, $this->permissionService);;
    }

    public function testProcessWithAuthenticatedUser()
    {
        $user = $this->createMock(User::class);
        $request = new ServerRequest();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = new Response();
        $this->session->set('user', 1);
        $this->userService->method('findUserById')
            ->with(1)
            ->willReturn($user);

        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($response);

        $result = $this->middleware->process($request, $handler);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testProcessWithoutAuthenticatedUser()
    {
        $request = new ServerRequest();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $this->session->set('user', null);
        $handler->expects($this->never())->method('handle');
        $result = $this->middleware->process($request, $handler);
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }
}
