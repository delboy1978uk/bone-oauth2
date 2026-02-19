<?php

namespace Bone\OAuth2\Test\Unit\Repository;

use Bone\OAuth2\Repository\UserRepository;
use Del\Entity\User;
use Codeception\Test\Unit;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class UserRepositoryUncoveredTest extends Unit
{
    public function testGetUserDetailsWithValidUser()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $userRepo = $this->createMock(EntityRepository::class);
        
        $user = new User();
        $user->setId(1);
        $user->setUsername('testuser');
        $user->setEmail('test@example.com');
        
        $userRepo->method('find')
            ->with(1)
            ->willReturn($user);
        
        $em->method('getRepository')
            ->willReturn($userRepo);
        
        $repository = new UserRepository($em);
        
        $result = $repository->getUserDetails(1);
        
        $this->assertIsArray($result);
        $this->assertEquals('testuser', $result['username']);
        $this->assertEquals('test@example.com', $result['email']);
    }

    public function testGetUserDetailsWithInvalidUser()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $userRepo = $this->createMock(EntityRepository::class);
        
        $userRepo->method('find')
            ->with(999)
            ->willReturn(null);
        
        $em->method('getRepository')
            ->willReturn($userRepo);
        
        $repository = new UserRepository($em);
        
        $result = $repository->getUserDetails(999);
        
        $this->assertNull($result);
    }
}
