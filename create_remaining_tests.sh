#!/bin/bash

# AuthServerControllerTest.php
cat > tests/Unit/Controller/AuthServerControllerTest.php << 'EOF'
<?php

declare(strict_types=1);

namespace Tests\Unit\Controller;

use Bone\OAuth2\Controller\AuthServerController;
use Bone\OAuth2\Entity\OAuthUser;
use Bone\OAuth2\Service\ClientService;
use Bone\OAuth2\Service\PermissionService;
use Bone\Server\Session;
use Codeception\Test\Unit;
use Del\Entity\User;
use Del\Service\UserService;
use Laminas\Diactoros\ServerRequest;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Psr\Http\Message\ResponseInterface;

class AuthServerControllerTest extends Unit
{
    private AuthServerController $controller;
    private AuthorizationServer $server;
    private UserService $userService;
    private PermissionService $permissionService;
    private ClientService $clientService;
    private Session $session;

    protected function _before()
    {
        $this->server = $this->createMock(AuthorizationServer::class);
        $this->userService = $this->createMock(UserService::class);
        $this->permissionService = $this->createMock(PermissionService::class);
        $this->clientService = $this->createMock(ClientService::class);
        $this->session = $this->createMock(Session::class);

        $this->controller = new AuthServerController(
            $this->server,
            $this->userService,
            $this->permissionService,
            $this->clientService
        );
        $this->controller->setSession($this->session);
    }

    public function testAccessTokenActionSuccess()
    {
        $request = new ServerRequest();
        $response = $this->createMock(ResponseInterface::class);
        
        $this->server->expects($this->once())
            ->method('respondToAccessTokenRequest')
            ->willReturn($response);

        $result = $this->controller->accessTokenAction($request, []);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testAccessTokenActionWithOAuthException()
    {
        $request = new ServerRequest();
        $exception = OAuthServerException::invalidRequest('test');
        
        $this->server->expects($this->once())
            ->method('respondToAccessTokenRequest')
            ->willThrowException($exception);

        $result = $this->controller->accessTokenAction($request, []);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testRegisterAction()
    {
        $request = new ServerRequest();
        $request = $request->withParsedBody([
            'name' => 'Test Client',
            'redirect_uris' => 'https://example.com/callback'
        ]);

        $response = $this->createMock(ResponseInterface::class);
        $this->clientService->expects($this->once())
            ->method('registerNewClient')
            ->willReturn($response);

        $result = $this->controller->registerAction($request, []);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
EOF

# AuthServerMiddlewareTest.php
cat > tests/Unit/Http/Middleware/AuthServerMiddlewareTest.php << 'EOF'
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
EOF

# ResourceServerMiddlewareTest.php
cat > tests/Unit/Http/Middleware/ResourceServerMiddlewareTest.php << 'EOF'
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
EOF

# ScopeCheckTest.php
cat > tests/Unit/Http/Middleware/ScopeCheckTest.php << 'EOF'
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
EOF

echo "All test files created successfully!"
