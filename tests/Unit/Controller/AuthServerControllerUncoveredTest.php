<?php

namespace Bone\OAuth2\Test\Unit\Controller;

use Bone\OAuth2\Controller\AuthServerController;
use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\OAuthUser;
use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Repository\ClientRepository;
use Bone\OAuth2\Repository\ScopeRepository;
use Bone\User\Entity\User;
use Codeception\Test\Unit;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseInterface;

class AuthServerControllerUncoveredTest extends Unit
{
    public function testAuthorizeActionWithValidRequest()
    {
        $authServer = $this->createMock(AuthorizationServer::class);
        $clientRepo = $this->createMock(ClientRepository::class);
        $scopeRepo = $this->createMock(ScopeRepository::class);
        
        $controller = new AuthServerController($authServer);
        
        // Create a request with query parameters
        $request = new ServerRequest();
        $request = $request->withQueryParams([
            'client_id' => 'test-client',
            'redirect_uri' => 'https://example.com/callback',
            'response_type' => 'code',
            'scope' => 'read write',
            'state' => 'xyz'
        ]);
        
        // Mock user in session
        $user = new User();
        $user->setId(1);
        $user->setUsername('testuser');
        $request = $request->withAttribute('user', $user);
        
        // Mock client
        $client = new Client();
        $client->setIdentifier('test-client');
        $client->setName('Test Client');
        $client->setDescription('Test Description');
        $client->setIcon('icon.png');
        
        $clientRepo->method('getClientEntity')
            ->willReturn($client);
        
        $request = $request->withAttribute('clientRepository', $clientRepo);
        
        // Mock scopes
        $scope1 = new Scope();
        $scope1->setIdentifier('read');
        $scope1->setDescription('Read access');
        
        $scope2 = new Scope();
        $scope2->setIdentifier('write');
        $scope2->setDescription('Write access');
        
        $scopeRepo->method('getScopeEntityByIdentifier')
            ->willReturnMap([
                ['read', $scope1],
                ['write', $scope2]
            ]);
        
        $request = $request->withAttribute('scopeRepository', $scopeRepo);
        
        $response = $controller->authorizeAction($request, new Response());
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testAuthorizeActionWithMissingUser()
    {
        $authServer = $this->createMock(AuthorizationServer::class);
        $controller = new AuthServerController($authServer);
        
        $request = new ServerRequest();
        $request = $request->withQueryParams([
            'client_id' => 'test-client',
            'redirect_uri' => 'https://example.com/callback'
        ]);
        
        $response = $controller->authorizeAction($request, new Response());
        
        // Should redirect to login
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testAuthorizeActionWithOAuthException()
    {
        $authServer = $this->createMock(AuthorizationServer::class);
        $clientRepo = $this->createMock(ClientRepository::class);
        
        $controller = new AuthServerController($authServer);
        
        $request = new ServerRequest();
        $request = $request->withQueryParams([
            'client_id' => 'invalid-client',
            'redirect_uri' => 'https://example.com/callback'
        ]);
        
        $user = new User();
        $user->setId(1);
        $request = $request->withAttribute('user', $user);
        
        // Mock client repository to throw exception
        $clientRepo->method('getClientEntity')
            ->willThrowException(OAuthServerException::invalidClient($request));
        
        $request = $request->withAttribute('clientRepository', $clientRepo);
        
        $response = $controller->authorizeAction($request, new Response());
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testLoginAsSomeoneElse()
    {
        $authServer = $this->createMock(AuthorizationServer::class);
        $controller = new AuthServerController($authServer);
        
        $request = new ServerRequest();
        $request = $request->withQueryParams([
            'client_id' => 'test-client',
            'redirect_uri' => 'https://example.com/callback',
            'state' => 'xyz'
        ]);
        
        $response = $controller->loginAsSomeoneElse($request, new Response());
        
        // Should redirect to login with query parameters preserved
        $this->assertEquals(302, $response->getStatusCode());
        $location = $response->getHeaderLine('Location');
        $this->assertStringContainsString('/login', $location);
    }

    public function testAccessTokenActionWithException()
    {
        $authServer = $this->createMock(AuthorizationServer::class);
        $controller = new AuthServerController($authServer);
        
        $request = new ServerRequest();
        $response = new Response();
        
        // Mock the authorization server to throw an exception
        $authServer->method('respondToAccessTokenRequest')
            ->willThrowException(OAuthServerException::invalidRequest('grant_type'));
        
        $result = $controller->accessTokenAction($request, $response);
        
        $this->assertInstanceOf(ResponseInterface::class, $result);
        // The exception should be caught and converted to a response
        $this->assertGreaterThanOrEqual(400, $result->getStatusCode());
    }
}
