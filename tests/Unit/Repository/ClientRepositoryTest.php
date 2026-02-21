<?php

declare(strict_types=1);

namespace Tests\Unit\Repository;

use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Repository\ClientRepository;
use Codeception\Test\Unit;
use Doctrine\ORM\EntityManagerInterface;

class ClientRepositoryTest extends Unit
{
    private ClientRepository $repository;
    private EntityManagerInterface $entityManager;

    protected function _before()
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        $this->repository = $this->getMockBuilder(ClientRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntityManager', 'findOneBy'])
            ->getMock();
            
        $this->repository->method('getEntityManager')
            ->willReturn($this->entityManager);
    }

    public function testGetClientEntity()
    {
        $client = new Client();
        $client->setIdentifier('test-client');
        
        $this->repository->method('findOneBy')
            ->with(['identifier' => 'test-client'])
            ->willReturn($client);
        
        $result = $this->repository->getClientEntity('test-client');
        
        $this->assertSame($client, $result);
    }

    public function testGetClientEntityReturnsNullWhenNotFound()
    {
        $this->repository->method('findOneBy')
            ->with(['identifier' => 'non-existent'])
            ->willReturn(null);
        
        $result = $this->repository->getClientEntity('non-existent');
        
        $this->assertNull($result);
    }

    public function testCreate()
    {
        $client = new Client();
        $client->setIdentifier('new-client');
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($client);
            
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $result = $this->repository->create($client);
        
        $this->assertSame($client, $result);
    }

    public function testSave()
    {
        $client = new Client();
        $client->setIdentifier('existing-client');
        
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $result = $this->repository->save($client);
        
        $this->assertSame($client, $result);
    }

    public function testDelete()
    {
        $client = new Client();
        $client->setIdentifier('client-to-delete');
        
        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($client);
            
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $this->repository->delete($client);
    }

    public function testValidateClientWithConfidentialClientAndCorrectSecret()
    {
        $client = new Client();
        $client->setIdentifier('test-client');
        $client->setSecret('correct-secret');
        $client->setConfidential(true);
        
        $this->repository->method('findOneBy')
            ->with(['identifier' => 'test-client'])
            ->willReturn($client);
        
        $result = $this->repository->validateClient('test-client', 'correct-secret', 'authorization_code');
        
        $this->assertTrue($result);
    }

    public function testValidateClientWithConfidentialClientAndIncorrectSecret()
    {
        $client = new Client();
        $client->setIdentifier('test-client');
        $client->setSecret('correct-secret');
        $client->setConfidential(true);
        
        $this->repository->method('findOneBy')
            ->with(['identifier' => 'test-client'])
            ->willReturn($client);
        
        $result = $this->repository->validateClient('test-client', 'wrong-secret', 'authorization_code');
        
        $this->assertFalse($result);
    }

    public function testValidateClientWithPublicClient()
    {
        $client = new Client();
        $client->setIdentifier('test-client');
        $client->setConfidential(false);
        
        $this->repository->method('findOneBy')
            ->with(['identifier' => 'test-client'])
            ->willReturn($client);
        
        $result = $this->repository->validateClient('test-client', null, 'authorization_code');
        
        $this->assertTrue($result);
    }
}
