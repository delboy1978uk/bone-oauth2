<?php

declare(strict_types=1);

namespace Tests\Unit\Repository;

use Bone\OAuth2\Entity\AccessToken;
use Bone\OAuth2\Entity\RefreshToken;
use Bone\OAuth2\Repository\RefreshTokenRepository;
use Codeception\Test\Unit;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;

class RefreshTokenRepositoryTest extends Unit
{
    private RefreshTokenRepository $repository;
    private EntityManagerInterface $entityManager;
    private UnitOfWork $unitOfWork;

    protected function _before()
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->unitOfWork = $this->createMock(UnitOfWork::class);
        
        $this->repository = $this->getMockBuilder(RefreshTokenRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntityManager', 'findOneBy'])
            ->getMock();
            
        $this->repository->method('getEntityManager')
            ->willReturn($this->entityManager);
            
        $this->entityManager->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);
    }

    public function testGetNewRefreshToken()
    {
        $refreshToken = $this->repository->getNewRefreshToken();
        
        $this->assertInstanceOf(RefreshToken::class, $refreshToken);
    }

    public function testPersistNewRefreshTokenWithManagedAccessToken()
    {
        $accessToken = new AccessToken();
        $accessToken->setIdentifier('test-access-token');
        
        $refreshToken = new RefreshToken();
        $refreshToken->setIdentifier('test-refresh-token');
        $refreshToken->setAccessToken($accessToken);
        
        $this->unitOfWork->method('getEntityState')
            ->with($accessToken)
            ->willReturn(UnitOfWork::STATE_MANAGED);
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($refreshToken);
            
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $this->repository->persistNewRefreshToken($refreshToken);
    }

    public function testPersistNewRefreshTokenWithUnmanagedAccessToken()
    {
        $accessToken = new AccessToken();
        $accessToken->setIdentifier('test-access-token');
        
        $refreshToken = new RefreshToken();
        $refreshToken->setIdentifier('test-refresh-token');
        $refreshToken->setAccessToken($accessToken);
        
        $this->unitOfWork->method('getEntityState')
            ->with($accessToken)
            ->willReturn(UnitOfWork::STATE_NEW);
        
        $this->entityManager->expects($this->once())
            ->method('getReference')
            ->with(AccessToken::class, 'test-access-token')
            ->willReturn($accessToken);
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($refreshToken);
            
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $this->repository->persistNewRefreshToken($refreshToken);
    }

    public function testRevokeRefreshTokenSuccess()
    {
        $refreshToken = new RefreshToken();
        $refreshToken->setIdentifier('test-token');
        
        $this->repository->method('findOneBy')
            ->with(['identifier' => 'test-token'])
            ->willReturn($refreshToken);
        
        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($refreshToken);
            
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $result = $this->repository->revokeRefreshToken('test-token');
    }

    public function testRevokeRefreshTokenNotFound()
    {
        $this->repository->method('findOneBy')
            ->with(['identifier' => 'non-existent'])
            ->willReturn(null);
        
        $this->entityManager->expects($this->never())
            ->method('remove');
        
        $result = $this->repository->revokeRefreshToken('non-existent');
    }

    public function testIsRefreshTokenRevokedReturnsTrueWhenNotFound()
    {
        $this->repository->method('findOneBy')
            ->with(['identifier' => 'non-existent'])
            ->willReturn(null);
        
        $result = $this->repository->isRefreshTokenRevoked('non-existent');
        
        $this->assertTrue($result);
    }

    public function testIsRefreshTokenRevokedReturnsTrueWhenRevoked()
    {
        $refreshToken = new RefreshToken();
        $refreshToken->setIdentifier('test-token');
        $refreshToken->setRevoked(true);
        
        $this->repository->method('findOneBy')
            ->with(['identifier' => 'test-token'])
            ->willReturn($refreshToken);
        
        $result = $this->repository->isRefreshTokenRevoked('test-token');
        
        $this->assertTrue($result);
    }

    public function testIsRefreshTokenRevokedReturnsFalseWhenNotRevoked()
    {
        $refreshToken = new RefreshToken();
        $refreshToken->setIdentifier('test-token');
        $refreshToken->setRevoked(false);
        
        $this->repository->method('findOneBy')
            ->with(['identifier' => 'test-token'])
            ->willReturn($refreshToken);
        
        $result = $this->repository->isRefreshTokenRevoked('test-token');
        
        $this->assertFalse($result);
    }
}
