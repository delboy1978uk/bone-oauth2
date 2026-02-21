<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Entity\UserApprovedScope;
use Bone\OAuth2\Repository\UserApprovedScopeRepository;
use Bone\OAuth2\Service\PermissionService;
use Codeception\Test\Unit;
use Del\Entity\User;

class PermissionServiceTest extends Unit
{
    private PermissionService $service;
    private UserApprovedScopeRepository $repository;

    protected function _before()
    {
        $this->repository = $this->createMock(UserApprovedScopeRepository::class);
        $this->service = new PermissionService($this->repository);
    }

    public function testGetRepository()
    {
        $result = $this->service->getRepository();
        
        $this->assertSame($this->repository, $result);
    }

    public function testGetScopes()
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(123);
        
        $client = new Client();
        $client->setId(456);
        
        $approvedScope1 = new UserApprovedScope();
        $approvedScope2 = new UserApprovedScope();
        
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with([
                'user' => 123,
                'client' => 456,
            ])
            ->willReturn([$approvedScope1, $approvedScope2]);
        
        $result = $this->service->getScopes($user, $client);
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContains($approvedScope1, $result);
        $this->assertContains($approvedScope2, $result);
    }

    public function testAddScopes()
    {
        $user = $this->createMock(User::class);
        $client = new Client();
        
        $scope1 = new Scope();
        $scope1->setIdentifier('read');
        
        $scope2 = new Scope();
        $scope2->setIdentifier('write');
        
        $scopes = [$scope1, $scope2];
        
        $this->repository->expects($this->exactly(2))
            ->method('save')
            ->with($this->isInstanceOf(UserApprovedScope::class));
        
        $this->service->addScopes($user, $client, $scopes);
    }

    public function testAddScopesWithEmptyArray()
    {
        $user = $this->createMock(User::class);
        $client = new Client();
        
        $this->repository->expects($this->never())
            ->method('save');
        
        $this->service->addScopes($user, $client, []);
    }
}
