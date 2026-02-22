<?php

declare(strict_types=1);

namespace Tests\Unit\Controller;

use Bone\OAuth2\Controller\AuthServerController;
use Bone\OAuth2\Entity\OAuthUser;
use Bone\OAuth2\Service\ClientService;
use Bone\OAuth2\Service\PermissionService;
use Del\SessionManager;
use Codeception\Test\Unit;
use Del\Entity\User;
use Del\Service\UserService;
use Laminas\Diactoros\ServerRequest;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Bone\OAuth2\Entity\Client;

use Bone\Http\Response\Json\Error\ServerErrorResponse;
use Laminas\Diactoros\Response;

use Psr\Http\Message\UriInterface;

class AuthServerControllerUncoveredTest extends Unit
{
    private AuthServerController $controller;
    private AuthorizationServer $server;
    private UserService $userService;
    private PermissionService $permissionService;
    private ClientService $clientService;
    private SessionManager $session;

    protected function _before()
    {
        $this->server = $this->createMock(AuthorizationServer::class);
        $this->userService = $this->createMock(UserService::class);
        $this->permissionService = $this->createMock(PermissionService::class);
        $this->clientService = $this->createMock(ClientService::class);
        $this->session = SessionManager::getInstance();

        $this->controller = new AuthServerController(
            $this->server,
            $this->userService,
            $this->permissionService,
            $this->clientService
        );
        $this->controller->setSession($this->session);
    }

    protected function _after()
    {
        $this->session->unset('user');
    }

    public function testAuthorizeActionRedirectsToLoginWhenNotLoggedIn()
    {
        $this->session->unset('user');
        $request = new ServerRequest();
        
        $response = $this->controller->authorizeAction($request, []);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    public function testAuthorizeActionSuccess()
    {
        $this->session->set('user', 123);
        $request = new ServerRequest();
        
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(123);
        
        $this->userService->expects($this->once())
            ->method('findUserById')
            ->with(123)
            ->willReturn($user);
            
        $authRequest = $this->createMock(AuthorizationRequest::class);
        $client = $this->createMock(Client::class);
        $authRequest->method('getClient')->willReturn($client);
        
        $this->server->expects($this->once())
            ->method('validateAuthorizationRequest')
            ->with($request)
            ->willReturn($authRequest);
            
        $this->server->expects($this->once())
            ->method('completeAuthorizationRequest')
            ->willReturn($this->createMock(ResponseInterface::class));
            
        $response = $this->controller->authorizeAction($request, []);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testAuthorizeActionWithOAuthException()
    {
        $this->session->set('user', 123);
        $request = new ServerRequest();
        
        $this->userService->method('findUserById')->willReturn($this->createMock(User::class));
        
        $exception = OAuthServerException::invalidRequest('test');
        
        $this->server->expects($this->once())
            ->method('validateAuthorizationRequest')
            ->willThrowException($exception);
            
        $response = $this->controller->authorizeAction($request, []);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $payload = json_decode((string)$response->getBody(), true);
        $this->assertArrayHasKey('error', $payload);
    }

    public function testLoginAsSomeoneElse()
    {
        $this->session->set('user', 123);
        $request = new ServerRequest();
        
        // Mock auth request in session
        $authRequest = $this->createMock(ServerRequest::class);
        $uri = $this->createMock(UriInterface::class);
        $uri->method('__toString')->willReturn('https://example.com/auth');
        $authRequest->method('getUri')->willReturn($uri);
        
        $this->session->set('authRequest', serialize($authRequest));
        
        $response = $this->controller->loginAsSomeoneElse($request, []);
        
        $this->assertNull($this->session->get('user'));
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('https://example.com/auth', $response->getHeaderLine('Location'));
    }

    public function testAuthorizeActionWithGenericException()
    {
        $this->session->set('user', 123);
        $request = new ServerRequest();

        $this->userService->method('findUserById')->willReturn($this->createMock(User::class));

        $this->server->expects($this->once())
            ->method('validateAuthorizationRequest')
            ->willThrowException(new \Exception('Generic error', 500));

        $response = $this->controller->authorizeAction($request, []);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testAuthorizeActionWithRedirectUriStartingWithQuestionMark()
    {
        $this->session->set('user', 123);
        $request = new ServerRequest();

        $user = $this->createMock(User::class);
        $this->userService->method('findUserById')->willReturn($user);

        $authRequest = $this->createMock(AuthorizationRequest::class);
        $authRequest->method('getClient')->willReturn($this->createMock(Client::class));

        $this->server->method('validateAuthorizationRequest')->willReturn($authRequest);

        $responseMock = new Response();
        $responseMock = $responseMock->withHeader('Location', '?code=123&state=abc');

        $this->server->method('completeAuthorizationRequest')->willReturn($responseMock);

        $response = $this->controller->authorizeAction($request, []);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $payload = json_decode((string)$response->getBody(), true);
        $this->assertEquals('123', $payload['code']);
        $this->assertEquals('abc', $payload['state']);
    }

    public function testAccessTokenActionWithGenericException()
    {
        $request = new ServerRequest();

        $this->server->expects($this->once())
            ->method('respondToAccessTokenRequest')
            ->willThrowException(new \Exception('Server error'));

        $response = $this->controller->accessTokenAction($request, []);

        // ServerErrorResponse usually returns 500
        $this->assertEquals(500, $response->getStatusCode());
    }
}
