<?php

namespace Bone\OAuth2\Test\Unit\Http\Middleware;

use Bone\OAuth2\Http\Middleware\AuthServerMiddleware;
use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Service\PermissionService;
use Bone\View\ViewEngineInterface;
use Del\Service\UserService;
use Del\SessionManager;
use Codeception\Test\Unit;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Del\Entity\User;

class AuthServerMiddlewareTest extends Unit
{
    private function getMiddleware($authServer, $view = null, $session = null)
    {
        return new AuthServerMiddleware(
            $this->createMock(UserService::class),
            $view ?: $this->createMock(ViewEngineInterface::class),
            $session ?: SessionManager::getInstance(),
            $authServer,
            $this->createMock(PermissionService::class)
        );
    }

    public function testProcessWithValidAuthorizationRequest()
    {
        $authServer = $this->createMock(AuthorizationServer::class);
        $view = $this->createMock(ViewEngineInterface::class);
        $view->method('render')->willReturn('<html></html>');

        $middleware = $this->getMiddleware($authServer, $view);

        $request = new ServerRequest();
        $request = $request->withMethod('GET');
        $user = new User();
        $request = $request->withAttribute('user', $user);
        $request = $request->withQueryParams([
            'response_type' => 'code',
            'client_id' => 'test-client'
        ]);

        $handler = $this->createMock(RequestHandlerInterface::class);

        $authRequest = $this->createMock(AuthorizationRequest::class);
        $client = $this->createMock(Client::class);
        $client->method('isProprietary')->willReturn(false);
        $authRequest->method('getClient')->willReturn($client);
        $authRequest->method('getScopes')->willReturn(['basic' => new Scope()]);

        $authServer->expects($this->once())
            ->method('validateAuthorizationRequest')
            ->willReturn($authRequest);

        $response = $middleware->process($request, $handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testProcessWithOAuthException()
    {
        $authServer = $this->createMock(AuthorizationServer::class);
        $middleware = $this->getMiddleware($authServer);

        $request = new ServerRequest();
        $request = $request->withMethod('GET');
        $request = $request->withQueryParams([
            'response_type' => 'code',
            'client_id' => 'invalid-client'
        ]);

        $handler = $this->createMock(RequestHandlerInterface::class);

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
        $middleware = $this->getMiddleware($authServer);

        $request = new ServerRequest();
        $request = $request->withMethod('GET');

        $handler = $this->createMock(RequestHandlerInterface::class);

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
        $middleware = $this->getMiddleware($authServer);

        $request = new ServerRequest();
        $request = $request->withMethod('POST');
        $request = $request->withAttribute('user', new User());

        $handler = $this->createMock(RequestHandlerInterface::class);
        // In this case, the middleware returns a response and doesn't call the handler
        $handler->expects($this->never())
            ->method('handle');

        $authRequest = $this->createMock(AuthorizationRequest::class);
        $client = $this->createMock(Client::class);
        $client->method('isProprietary')->willReturn(true);
        $authRequest->method('getClient')->willReturn($client);

        $authServer->method('validateAuthorizationRequest')->willReturn($authRequest);
        SessionManager::getInstance()->set('authRequest', serialize(new ServerRequest()));

        $response = $middleware->process($request, $handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
