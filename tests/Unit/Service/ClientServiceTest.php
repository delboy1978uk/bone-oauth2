<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Form\RegisterClientForm;
use Bone\OAuth2\Repository\ClientRepository;
use Bone\OAuth2\Repository\ScopeRepository;
use Bone\OAuth2\Service\ClientService;
use Codeception\Test\Unit;
use Del\Entity\User;
use Del\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class ClientServiceTest extends Unit
{
    private ClientService $service;
    private ClientRepository $clientRepository;
    private EntityManagerInterface $entityManager;

    protected function _before()
    {
        $this->clientRepository = $this->createMock(ClientRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        $this->clientRepository->method('getEntityManager')
            ->willReturn($this->entityManager);
        
        $this->service = new ClientService($this->clientRepository);
    }

    public function testGetClientRepository()
    {
        $result = $this->service->getClientRepository();
        
        $this->assertSame($this->clientRepository, $result);
    }

    public function testGenerateSecret()
    {
        $client = new Client();
        $client->setName('Test Client');
        
        $result = $this->service->generateSecret($client);
        
        $this->assertSame($client, $result);
        $this->assertNotNull($client->getSecret());
        $this->assertNotEmpty($client->getSecret());
    }

    public function testDeleteClient()
    {
        $client = new Client();
        $client->setIdentifier('test-client');
        
        $this->clientRepository->expects($this->once())
            ->method('delete')
            ->with($client);
        
        $this->service->deleteClient($client);
    }

    public function testCreateFromArrayWithConfidentialTrue()
    {
        $user = $this->createMock(User::class);
        
        $data = [
            'name' => 'Test Client',
            'description' => 'Test Description',
            'icon' => 'fa-icon',
            'redirectUri' => 'https://example.com/callback',
            'grantType' => 'authorization_code',
            'confidential' => true,
        ];
        
        $client = $this->service->createFromArray($data, $user);
        
        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals('Test Client', $client->getName());
        $this->assertEquals('Test Description', $client->getDescription());
        $this->assertEquals('fa-icon', $client->getIcon());
        $this->assertEquals('https://example.com/callback', $client->getRedirectUri());
        $this->assertEquals('authorization_code', $client->getGrantType());
        $this->assertTrue($client->isConfidential());
        $this->assertNotNull($client->getSecret());
        $this->assertSame($user, $client->getUser());
    }

    public function testCreateFromArrayWithConfidentialString()
    {
        $user = $this->createMock(User::class);
        
        $data = [
            'name' => 'Test Client',
            'description' => 'Test Description',
            'icon' => 'fa-icon',
            'redirectUri' => 'https://example.com/callback',
            'grantType' => 'authorization_code',
            'confidential' => 'confidential',
        ];
        
        $client = $this->service->createFromArray($data, $user);
        
        $this->assertTrue($client->isConfidential());
    }

    public function testCreateFromArrayWithConfidentialFalse()
    {
        $user = $this->createMock(User::class);
        
        $data = [
            'name' => 'Test Client',
            'description' => 'Test Description',
            'icon' => 'fa-icon',
            'redirectUri' => 'https://example.com/callback',
            'grantType' => 'authorization_code',
            'confidential' => false,
        ];
        
        $client = $this->service->createFromArray($data, $user);
        
        $this->assertFalse($client->isConfidential());
    }
}
