<?php

declare(strict_types=1);

namespace Tests\Unit\Repository;

use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Exception\OAuthException;
use Bone\OAuth2\Repository\ScopeRepository;
use Codeception\Test\Unit;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;

class ScopeRepositoryTest extends Unit
{
    private ScopeRepository $repository;
    private EntityManagerInterface $entityManager;

    protected function _before()
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        $this->repository = $this->getMockBuilder(ScopeRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntityManager', 'findOneBy'])
            ->getMock();
            
        $this->repository->method('getEntityManager')
            ->willReturn($this->entityManager);
    }

    public function testGetScopeEntityByIdentifier()
    {
        $scope = new Scope();
        $scope->setIdentifier('read');
        
        $this->repository->method('findOneBy')
            ->with(['identifier' => 'read'])
            ->willReturn($scope);
        
        $result = $this->repository->getScopeEntityByIdentifier('read');
        
        $this->assertSame($scope, $result);
    }

    public function testGetScopeEntityByIdentifierReturnsNull()
    {
        $this->repository->method('findOneBy')
            ->with(['identifier' => 'non-existent'])
            ->willReturn(null);
        
        $result = $this->repository->getScopeEntityByIdentifier('non-existent');
        
        $this->assertNull($result);
    }

    public function testFinalizeScopesWithValidScopes()
    {
        $scope1 = new Scope();
        $scope1->setIdentifier('read');
        
        $scope2 = new Scope();
        $scope2->setIdentifier('write');
        
        $client = new Client();
        $client->setScopes(new ArrayCollection([$scope1, $scope2]));
        
        $requestedScopes = [$scope1, $scope2];
        
        $result = $this->repository->finalizeScopes($requestedScopes, 'authorization_code', $client, '123');
        
        $this->assertCount(2, $result);
    }

    public function testFinalizeScopesThrowsExceptionForUnauthorizedScopes()
    {
        $scope1 = new Scope();
        $scope1->setIdentifier('read');
        
        $scope2 = new Scope();
        $scope2->setIdentifier('write');
        
        $scope3 = new Scope();
        $scope3->setIdentifier('delete');
        
        $client = new Client();
        $client->setScopes(new ArrayCollection([$scope1, $scope2]));
        
        $requestedScopes = [$scope1, $scope2, $scope3];
        
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Scopes not authorised.');
        $this->expectExceptionCode(403);
        
        $this->repository->finalizeScopes($requestedScopes, 'authorization_code', $client, '123');
    }

    public function testCreate()
    {
        $scope = new Scope();
        $scope->setIdentifier('new-scope');
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($scope);
            
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $result = $this->repository->create($scope);
        
        $this->assertSame($scope, $result);
    }

    public function testSave()
    {
        $scope = new Scope();
        $scope->setIdentifier('existing-scope');
        
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $result = $this->repository->save($scope);
        
        $this->assertSame($scope, $result);
    }
}
