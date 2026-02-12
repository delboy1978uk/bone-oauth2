<?php

declare(strict_types=1);

namespace Tests\Unit\Repository;

use Bone\OAuth2\Entity\UserApprovedScope;
use Bone\OAuth2\Repository\UserApprovedScopeRepository;
use Codeception\Test\Unit;
use Doctrine\ORM\EntityManagerInterface;

class UserApprovedScopeRepositoryTest extends Unit
{
    private UserApprovedScopeRepository $repository;
    private EntityManagerInterface $entityManager;

    protected function _before()
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        $this->repository = $this->getMockBuilder(UserApprovedScopeRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntityManager'])
            ->getMock();
            
        $this->repository->method('getEntityManager')
            ->willReturn($this->entityManager);
    }

    public function testSave()
    {
        $userApprovedScope = new UserApprovedScope();
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($userApprovedScope);
            
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $this->repository->save($userApprovedScope);
    }
}
