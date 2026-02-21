<?php

declare(strict_types=1);

namespace Tests\Unit\Controller;

use Bone\Contracts\Service\TranslatorInterface;
use Bone\OAuth2\Controller\ApiKeyController;
use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Exception\OAuthException;
use Bone\OAuth2\Form\ApiKeyForm;
use Bone\OAuth2\Repository\ClientRepository;
use Bone\OAuth2\Service\ClientService;
use Bone\View\ViewEngineInterface;
use Codeception\Test\Unit;
use Del\Entity\User;
use Laminas\Diactoros\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class ApiKeyControllerTest extends Unit
{
    private ApiKeyController $controller;
    private ClientService $clientService;
    private ClientRepository $clientRepository;
    private ViewEngineInterface $viewEngine;
    private TranslatorInterface $translator;

    protected function _before()
    {
        $this->clientService = $this->createMock(ClientService::class);
        $this->clientRepository = $this->createMock(ClientRepository::class);
        $this->viewEngine = $this->createMock(ViewEngineInterface::class);
        $this->viewEngine->method('render')->willReturn('test');
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->clientService->method('getClientRepository')
            ->willReturn($this->clientRepository);

        $this->controller = new ApiKeyController($this->clientService);
        $this->controller->setTranslator($this->translator);;
        $this->controller->setView($this->viewEngine);;
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
        $this->clientRepository->method('findBy')->willReturn([$client1, $client2]);
        $response = $this->controller->myApiKeysAction($request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testDeleteConfirmAction()
    {
        $client = new Client();
        $client->setIdentifier('test-client');
        $client->setName('Test Client');
        $request = new ServerRequest();
        $request = $request->withAttribute('id', 'test-client');
        $this->clientRepository->method('find')->willReturn($client);
        $response = $this->controller->deleteConfirmAction($request);

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
        $request = $request->withAttribute('id', 'test-client')->withAttribute('user', $user);
        $this->clientRepository->method('find')->willReturn($client);
        $this->clientRepository->expects($this->once())->method('delete')->with($client);
        $response = $this->controller->deleteAction($request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
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
        $request = $request->withAttribute('id', 'test-client')->withAttribute('user', $user);
        $this->clientRepository->method('find')->willReturn($client);
        $this->clientService->expects($this->never())->method('deleteClient');
        $this->expectException(OAuthException::class);
        $this->controller->deleteAction($request);
    }

    public function testAddAction()
    {
        $request = new ServerRequest();

        $response = $this->controller->addAction($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testAddSubmitActionSuccess()
    {
        $user = $this->createMock(User::class);
        $request = new ServerRequest();
        $request = $request->withAttribute('user', $user)->withParsedBody([
            'name' => 'Test API Key',
            'description' => 'Test Description',
            'icon' => 'your-face.jpg',
            'callbackUrls' => 'https://example.com/callback',
            'grantType' => 'client_credentials',
            'confidential' => true,
        ]);
        $this->clientService->expects($this->once())->method('createFromArray');
        $response = $this->controller->addSubmitAction($request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testAddSubmitActionInvalidForm()
    {
        $form = $this->createMock(ApiKeyForm::class);
        $form->method('isValid')->willReturn(false);
        $request = new ServerRequest();
        $request = $request->withAttribute('form', $form)->withParsedBody(['name' => 'Test API Key', 'description' => 'Test Description']);
        $this->clientService->expects($this->never())->method('createFromArray');
        $response = $this->controller->addSubmitAction($request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
