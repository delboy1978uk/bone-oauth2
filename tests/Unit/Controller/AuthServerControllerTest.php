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
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseInterface;

class AuthServerControllerTest extends Unit
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
