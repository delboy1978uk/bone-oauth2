<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use Bone\OAuth2\Http\Middleware\AuthServerMiddleware;
use Bone\Server\Session;
use Codeception\Test\Unit;
use Del\Entity\User;
use Del\Service\UserService;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthServerMiddlewareTest extends Unit
{
    private AuthServerMiddleware $middleware;
    private UserService $userService;
    private Session $session;

    protected function _before()
    {
        $this->userService = $this->createMock(UserService::class);
        $this->session = $this->createMock(Session::class);
        $this->middleware = new AuthServerMiddleware($this->userService);
        $this->middleware->setSession($this->session);
    }

    public function testProcessWithAuthenticatedUser()
    {
        $user = $this->createMock(User::class);
        $request = new ServerRequest();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = new Response();

        $this->session->method('get')
            ->with('user')
            ->willReturn(1);

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

        $this->session->method('get')
            ->with('user')
            ->willReturn(null);

        $handler->expects($this->never())
            ->method('handle');

        $result = $this->middleware->process($request, $handler);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
    }
}
