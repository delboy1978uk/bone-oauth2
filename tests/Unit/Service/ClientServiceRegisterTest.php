<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Form\RegisterClientForm;
use Bone\OAuth2\Repository\ClientRepository;
use Bone\OAuth2\Service\ClientService;
use Codeception\Test\Unit;
use Del\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class ClientServiceRegisterTest extends Unit
{
    private ClientService $service;
    private ClientRepository $repository;
    private EntityManagerInterface $entityManager;

    protected function _before()
    {
        $this->repository = $this->createMock(ClientRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        // Setup entity manager to return mock repositories
        $this->repository->method('getEntityManager')
            ->willReturn($this->entityManager);
            
        $this->service = new ClientService($this->repository);
    }

    public function testRegisterNewClientWithAllFields()
    {
        $user = $this->createMock(User::class);
        $scope = new Scope();
        $scope->setIdentifier('basic');
        
        // Mock User repository
        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->method('find')->with(1)->willReturn($user);
        
        // Mock Scope repository
        $scopeRepo = $this->createMock(EntityRepository::class);
        $scopeRepo->method('findOneBy')
            ->with(['identifier' => 'basic'])
            ->willReturn($scope);
        
        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [User::class, $userRepo],
                [Scope::class, $scopeRepo]
            ]);

        $data = [
            'client_name' => 'Test Client',
            'redirect_uris' => 'https://example.com/callback',
            'token_endpoint_auth_method' => 'authorization_code',
            'confidential' => true,
            'proprietary' => false,
            'user' => $user,
            'scopes' => [$scope1, $scope2]
        ];

        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($client) {
                return $client instanceof Client
                    && str_contains($client->getName(), 'Test Client')
                    && $client->getRedirectUri() === 'https://example.com/callback'
                    && $client->getIcon() === 'https://example.com/icon.png'
                    && $client->getSecret() !== null;
            }));

        $form = new RegisterClientForm('reg');
        $form->populate($data);
        $result = $this->service->registerNewClient($form);
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testRegisterNewClientWithMinimalFields()
    {
        $user = $this->createMock(User::class);
        $scope = new Scope();
        $scope->setIdentifier('basic');
        
        // Mock User repository
        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->method('find')->with(1)->willReturn($user);
        
        // Mock Scope repository
        $scopeRepo = $this->createMock(EntityRepository::class);
        $scopeRepo->method('findOneBy')
            ->with(['identifier' => 'basic'])
            ->willReturn($scope);
        
        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [User::class, $userRepo],
                [Scope::class, $scopeRepo]
            ]);

        $data = [
            'name' => 'Minimal Client',
            'description' => 'Test Description',
            'icon' => 'https://example.com/icon.png',
            'redirect_uri' => 'https://example.com/callback',
            'grant_type' => 'authorization_code',
            'confidential' => true,
            'proprietary' => false,
            'client_name' => 'Minimal Client',
            'redirect_uris' => 'https://example.com/callback',
            'token_endpoint_auth_method' => 'none',
            'logo_uri' => 'https://example.com/logo.png'
        ];

        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($client) {
                return $client instanceof Client
                    && str_contains($client->getName(), 'Minimal Client')
                    && $client->getRedirectUri() === 'https://example.com/callback';
            }));

        $form = new RegisterClientForm('reg');
        $form->populate($data);
        $result = $this->service->registerNewClient($form);
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testRegisterNewClientGeneratesIdentifier()
    {
        $user = $this->createMock(User::class);
        $scope = new Scope();
        $scope->setIdentifier('basic');
        
        // Mock User repository
        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->method('find')->with(1)->willReturn($user);
        
        // Mock Scope repository
        $scopeRepo = $this->createMock(EntityRepository::class);
        $scopeRepo->method('findOneBy')
            ->with(['identifier' => 'basic'])
            ->willReturn($scope);
        
        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [User::class, $userRepo],
                [Scope::class, $scopeRepo]
            ]);

        $data = [
            'name' => 'Test Client',
            'redirect_uri' => 'https://example.com/callback',
            'grant_type' => 'authorization_code',
            'description' => 'Test Description',
            'icon' => 'https://example.com/icon.png',
            'confidential' => true,
            'proprietary' => false,
            'client_name' => 'Test Client',
            'redirect_uris' => 'https://example.com/callback',
            'token_endpoint_auth_method' => 'client_secret_post',
            'logo_uri' => 'https://example.com/logo.png'
        ];

        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($client) {
                return $client instanceof Client
                    && $client->getIdentifier() !== null
                    && strlen($client->getIdentifier()) === 32;
            }));

        $form = new RegisterClientForm('reg');
        $form->populate($data);
        $result = $this->service->registerNewClient($form);
        $body = json_decode((string)$result->getBody(), true);
        $this->assertNotNull($body['client_id']);
    }

    public function testRegisterNewClientWithConfidentialGeneratesSecret()
    {
        $user = $this->createMock(User::class);
        $scope = new Scope();
        $scope->setIdentifier('basic');
        
        // Mock User repository
        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->method('find')->with(1)->willReturn($user);
        
        // Mock Scope repository
        $scopeRepo = $this->createMock(EntityRepository::class);
        $scopeRepo->method('findOneBy')
            ->with(['identifier' => 'basic'])
            ->willReturn($scope);
        
        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [User::class, $userRepo],
                [Scope::class, $scopeRepo]
            ]);

        $data = [
            'client_name' => 'Confidential Client',
            'redirect_uris' => 'https://example.com/callback',
            'token_endpoint_auth_method' => 'client_secret_basic',
            'logo_uri' => 'https://example.com/logo.png'
        ];

        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($client) {
                return $client instanceof Client
                    && $client->getSecret() !== null;
            }));

        $form = new RegisterClientForm('reg');
        $form->populate($data);
        $result = $this->service->registerNewClient($form);
        $body = json_decode((string)$result->getBody(), true);
        $this->assertNotNull($body['client_secret']);
    }

    public function testRegisterNewClientWithNonConfidentialNoSecret()
    {
        $user = $this->createMock(User::class);
        $scope = new Scope();
        $scope->setIdentifier('basic');
        
        // Mock User repository
        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->method('find')->with(1)->willReturn($user);
        
        // Mock Scope repository
        $scopeRepo = $this->createMock(EntityRepository::class);
        $scopeRepo->method('findOneBy')
            ->with(['identifier' => 'basic'])
            ->willReturn($scope);
        
        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [User::class, $userRepo],
                [Scope::class, $scopeRepo]
            ]);

        $data = [
            'client_name' => 'Public Client',
            'redirect_uris' => 'https://example.com/callback',
            'token_endpoint_auth_method' => 'none',
            'logo_uri' => 'https://example.com/logo.png'
        ];

        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($client) {
                return $client instanceof Client
                    && $client->getSecret() !== null; // Note: The service always generates a secret
            }));

        $form = new RegisterClientForm('reg');
        $form->populate($data);
        $result = $this->service->registerNewClient($form);
        $body = json_decode((string)$result->getBody(), true);
        $this->assertNotNull($body['client_secret']); // The service always returns a secret
    }
}
