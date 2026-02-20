<?php

namespace Bone\OAuth2\Test\Unit\Controller;

use Bone\OAuth2\Controller\AuthServerController;
use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\OAuthUser;
use Del\Service\UserService;
use Bone\OAuth2\Service\PermissionService;
use Bone\OAuth2\Service\ClientService;
use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Repository\ClientRepository;
use Bone\OAuth2\Repository\ScopeRepository;
use Del\Entity\User;
use Del\SessionManager;
use Codeception\Test\Unit;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Psr\Http\Message\ResponseInterface;

class AuthServerControllerUncoveredTest extends Unit
{
    public function testAuthorizeActionWithValidRequest()
    {
        $authServer = $this->createMock(AuthorizationServer::class);
        $userService = $this->createMock(UserService::class);

        $user = new User();
        $user->setId(1);
        $userService->method('findUserById')->willReturn($user);

        $controller = new AuthServerController($authServer, $userService, $this->createMock(PermissionService::class), $this->createMock(ClientService::class));
        $controller->setSession(SessionManager::getInstance());
        SessionManager::getInstance()->set('user', 1);

        $request = new ServerRequest();
        $request = $request->withQueryParams([
            'client_id' => 'test-client',
            'redirect_uri' => 'https://example.com/callback',
            'response_type' => 'code',
            'scope' => 'read write',
            'state' => 'xyz'
        ]);

        $client = new Client();
        $client->setIdentifier('test-client');

        $authRequest = $this->createMock(AuthorizationRequest::class);
        $authRequest->method('getClient')->willReturn($client);
        $authServer->method('validateAuthorizationRequest')->willReturn($authRequest);

        $redirectResponse = new Response();
        $redirectResponse = $redirectResponse->withHeader('Location', 'https://example.com/callback?code=123');
        $authServer->method('completeAuthorizationRequest')->willReturn($redirectResponse);

        $response = $controller->authorizeAction($request, []);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testAuthorizeActionWithMissingUser()
    {
        $authServer = $this->createMock(AuthorizationServer::class);
        $userService = $this->createMock(UserService::class);
        $controller = new AuthServerController($authServer, $userService, $this->createMock(PermissionService::class), $this->createMock(ClientService::class));
        $controller->setSession(SessionManager::getInstance());
        SessionManager::getInstance()->set('user', null);

        $request = new ServerRequest();
        $request = $request->withQueryParams([
            'client_id' => 'test-client',
            'redirect_uri' => 'https://example.com/callback'
        ]);

        $response = $controller->authorizeAction($request, []);

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testAuthorizeActionWithOAuthException()
    {
        $authServer = $this->createMock(AuthorizationServer::class);
        $userService = $this->createMock(UserService::class);
        $user = new User();
        $user->setId(1);
        $userService->method('findUserById')->willReturn($user);

        $controller = new AuthServerController($authServer, $userService, $this->createMock(PermissionService::class), $this->createMock(ClientService::class));
        $controller->setSession(SessionManager::getInstance());
        SessionManager::getInstance()->set('user', 1);

        $request = new ServerRequest();
        $request = $request->withQueryParams([
            'client_id' => 'invalid-client',
            'redirect_uri' => 'https://example.com/callback'
        ]);

        $authServer->method('validateAuthorizationRequest')
            ->willThrowException(OAuthServerException::invalidClient($request));

        $response = $controller->authorizeAction($request, []);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testLoginAsSomeoneElse()
    {
        $authServer = $this->createMock(AuthorizationServer::class);
        $controller = new AuthServerController($authServer, $this->createMock(UserService::class), $this->createMock(PermissionService::class), $this->createMock(ClientService::class));
        $controller->setSession(SessionManager::getInstance());
        SessionManager::getInstance()->set('authRequest', serialize(new ServerRequest()));

        $request = new ServerRequest();
        $response = $controller->loginAsSomeoneElse($request, []);

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testAccessTokenActionWithException()
    {
        $authServer = $this->createMock(AuthorizationServer::class);
        $controller = new AuthServerController($authServer, $this->createMock(UserService::class), $this->createMock(PermissionService::class), $this->createMock(ClientService::class));
        $controller->setSession(SessionManager::getInstance());

        $request = new ServerRequest();
        $authServer->method('respondToAccessTokenRequest')
            ->willThrowException(OAuthServerException::invalidRequest('grant_type'));

        $result = $controller->accessTokenAction($request, []);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertGreaterThanOrEqual(400, $result->getStatusCode());
    }
}
