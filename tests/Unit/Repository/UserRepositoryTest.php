<?php

declare(strict_types=1);

namespace Tests\Unit\Repository;

use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Repository\UserRepository;
use Codeception\Test\Unit;
use Del\Entity\User;

class UserRepositoryTest extends Unit
{
    private UserRepository $repository;

    protected function _before()
    {
        $this->repository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findOneBy'])
            ->getMock();
    }

    public function testGetUserEntityByUserCredentialsWithValidCredentials()
    {
        $user = $this->createMock(User::class);
        $user->method('getPassword')
            ->willReturn('$2y$14$' . substr(password_hash('correct-password', PASSWORD_BCRYPT, ['cost' => 14]), 7));
        
        $this->repository->method('findOneBy')
            ->with(['email' => 'test@example.com'])
            ->willReturn($user);
        
        $client = new Client();
        
        $result = $this->repository->getUserEntityByUserCredentials(
            'test@example.com',
            'correct-password',
            'password',
            $client
        );
        
        $this->assertSame($user, $result);
    }

    public function testGetUserEntityByUserCredentialsWithInvalidPassword()
    {
        $user = $this->createMock(User::class);
        $user->method('getPassword')
            ->willReturn('$2y$14$' . substr(password_hash('correct-password', PASSWORD_BCRYPT, ['cost' => 14]), 7));
        
        $this->repository->method('findOneBy')
            ->with(['email' => 'test@example.com'])
            ->willReturn($user);
        
        $client = new Client();
        
        $result = $this->repository->getUserEntityByUserCredentials(
            'test@example.com',
            'wrong-password',
            'password',
            $client
        );
        
        $this->assertNull($result);
    }

    public function testGetUserEntityByUserCredentialsWithNonExistentUser()
    {
        $this->repository->method('findOneBy')
            ->with(['email' => 'nonexistent@example.com'])
            ->willReturn(null);
        
        $client = new Client();
        
        $result = $this->repository->getUserEntityByUserCredentials(
            'nonexistent@example.com',
            'any-password',
            'password',
            $client
        );
        
        $this->assertNull($result);
    }

    public function testCheckUserCredentialsWithValidCredentials()
    {
        $user = $this->createMock(User::class);
        $user->method('verifyPassword')
            ->with('correct-password')
            ->willReturn(true);
        
        $this->repository->method('findOneBy')
            ->with(['email' => 'test@example.com'])
            ->willReturn($user);
        
        $result = $this->repository->checkUserCredentials('test@example.com', 'correct-password');
        
        $this->assertTrue($result);
    }

    public function testCheckUserCredentialsWithInvalidCredentials()
    {
        $user = $this->createMock(User::class);
        $user->method('verifyPassword')
            ->with('wrong-password')
            ->willReturn(false);
        
        $this->repository->method('findOneBy')
            ->with(['email' => 'test@example.com'])
            ->willReturn($user);
        
        $result = $this->repository->checkUserCredentials('test@example.com', 'wrong-password');
        
        $this->assertFalse($result);
    }

    public function testCheckUserCredentialsWithNonExistentUser()
    {
        $this->repository->method('findOneBy')
            ->with(['email' => 'nonexistent@example.com'])
            ->willReturn(null);
        
        $result = $this->repository->checkUserCredentials('nonexistent@example.com', 'any-password');
        
        $this->assertFalse($result);
    }

    public function testGetUserDetailsReturnsNull()
    {
        $this->repository->method('findOneBy')
            ->with(['email' => 'test@example.com'])
            ->willReturn(null);
        
        $result = $this->repository->getUserDetails('test@example.com');
        
        $this->assertNull($result);
    }
}
