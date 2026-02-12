<?php

declare(strict_types=1);

namespace Tests\Unit\Controller;

use Bone\OAuth2\Controller\ApiKeyController;
use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Form\ApiKeyForm;
use Bone\OAuth2\Repository\ClientRepository;
use Bone\OAuth2\Service\ClientService;
use Bone\View\ViewEngine;
use Codeception\Test\Unit;
use Del\Entity\User;
use Del\Form\Form;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class ApiKeyControllerTest extends Unit
{
    private ApiKeyController $controller;
    private ClientService $clientService;
    private ClientRepository $clientRepository;
    private ViewEngine $viewEngine;

    protected function _before()
    {
        $this->clientService = $this->createMock(ClientService::class);
        $this->clientRepository = $this->createMock(ClientRepository::class);
        $this->viewEngine = $this->createMock(ViewEngine::class);

        $this->clientService->method('getClientRepository')
            ->willReturn($this->clientRepository);

        $this->controller = new ApiKeyController($this->clientService);
    }

    public function testMyApiKeysAction()
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        $client1 = new Client();
        $client1->setName('Client 1');
        $client2 = new Client();
        $client2->setName('Client 2');

        $request = new ServerRequest();
        $request = $request->withAttribute('user', $user);

        $this->clientRepository->method('findBy')
            ->with(['user' => $user, 'proprietary' => true])
            ->willReturn([$client1, $client2]);

        $response = $this->controller->myApiKeysAction($request, $this->viewEngine);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testDeleteConfirmAction()
    {
        $client = new Client();
        $client->setIdentifier('test-client');
        $client->setName('Test Client');

        $request = new ServerRequest();
        $request = $request->withAttribute('id', 'test-client');

        $this->clientRepository->method('getClientEntity')
            ->with('test-client')
            ->willReturn($client);

        $response = $this->controller->deleteConfirmAction($request, $this->viewEngine);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testDeleteActionSuccess()
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        $client = new Client();
        $client->setIdentifier('test-client');
        $client->setUser($user);

        $request = new ServerRequest();
        $request = $request->withAttribute('id', 'test-client')
                          ->withAttribute('user', $user);

        $this->clientRepository->method('getClientEntity')
            ->with('test-client')
            ->willReturn($client);

        $this->clientService->expects($this->once())
            ->method('deleteClient')
            ->with($client);

        $response = $this->controller->deleteAction($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testDeleteActionUnauthorized()
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        $otherUser = $this->createMock(User::class);
        $otherUser->method('getId')->willReturn(2);

        $client = new Client();
        $client->setIdentifier('test-client');
        $client->setUser($otherUser);

        $request = new ServerRequest();
        $request = $request->withAttribute('id', 'test-client')
                          ->withAttribute('user', $user);

        $this->clientRepository->method('getClientEntity')
            ->with('test-client')
            ->willReturn($client);

        $this->clientService->expects($this->never())
            ->method('deleteClient');

        $response = $this->controller->deleteAction($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testAddAction()
    {
        $request = new ServerRequest();

        $response = $this->controller->addAction($request, $this->viewEngine);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testAddSubmitActionSuccess()
    {
        $user = $this->createMock(User::class);

        $form = $this->createMock(ApiKeyForm::class);
        $form->method('isValid')->willReturn(true);
        $form->method('getData')->willReturn([
            'name' => 'Test API Key',
            'description' => 'Test Description'
        ]);

        $request = new ServerRequest();
        $request = $request->withAttribute('user', $user)
                          ->withAttribute('form', $form);

        $this->clientService->expects($this->once())
            ->method('createFromArray');

        $response = $this->controller->addSubmitAction($request, $this->viewEngine);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testAddSubmitActionInvalidForm()
    {
        $form = $this->createMock(ApiKeyForm::class);
        $form->method('isValid')->willReturn(false);

        $request = new ServerRequest();
        $request = $request->withAttribute('form', $form);

        $this->clientService->expects($this->never())
            ->method('createFromArray');

        $response = $this->controller->addSubmitAction($request, $this->viewEngine);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
