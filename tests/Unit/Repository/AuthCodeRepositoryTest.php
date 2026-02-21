<?php

declare(strict_types=1);

namespace Tests\Unit\Repository;

use Bone\OAuth2\Entity\AuthCode;
use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Exception\OAuthException;
use Bone\OAuth2\Repository\AuthCodeRepository;
use Bone\OAuth2\Repository\ClientRepository;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class AuthCodeRepositoryTest extends Unit
{
    private AuthCodeRepository $repository;
    private EntityManagerInterface $entityManager;
    private ClientRepository $clientRepository;

    protected function _before()
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->clientRepository = $this->createMock(ClientRepository::class);
        
        $this->repository = $this->getMockBuilder(AuthCodeRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntityManager', 'findOneBy'])
            ->getMock();
            
        $this->repository->method('getEntityManager')
            ->willReturn($this->entityManager);
    }

    public function testGetNewAuthCode()
    {
        $authCode = $this->repository->getNewAuthCode();
        
        $this->assertInstanceOf(AuthCode::class, $authCode);
    }

    public function testPersistNewAuthCode()
    {
        $client = new Client();
        $client->setIdentifier('test-client');
        
        $authCode = new AuthCode();
        $authCode->setIdentifier('test-code');
        $authCode->setClient($client);
        
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Client::class)
            ->willReturn($this->clientRepository);
            
        $this->clientRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['identifier' => 'test-client'])
            ->willReturn($client);
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($authCode);
            
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $this->repository->persistNewAuthCode($authCode);
        
        $this->assertInstanceOf(DateTimeImmutable::class, $authCode->getExpiryDateTime());
    }

    public function testRevokeAuthCode()
    {
        $authCode = new AuthCode();
        $authCode->setIdentifier('test-code');
        $authCode->setRevoked(false);
        
        $this->repository->method('findOneBy')
            ->with(['identifier' => 'test-code'])
            ->willReturn($authCode);
            
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $this->repository->revokeAuthCode('test-code');
        
        $this->assertTrue($authCode->isRevoked());
    }

    public function testRevokeAuthCodeThrowsExceptionWhenNotFound()
    {
        $this->repository->method('findOneBy')
            ->with(['identifier' => 'non-existent'])
            ->willReturn(null);
        
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Token not found');
        $this->expectExceptionCode(404);
        
        $this->repository->revokeAuthCode('non-existent');
    }

    public function testIsAuthCodeRevokedReturnsTrueWhenNotFound()
    {
        $this->repository->method('findOneBy')
            ->with(['identifier' => 'non-existent'])
            ->willReturn(null);
        
        $result = $this->repository->isAuthCodeRevoked('non-existent');
        
        $this->assertTrue($result);
    }

    public function testIsAuthCodeRevokedReturnsTrueWhenRevoked()
    {
        $authCode = new AuthCode();
        $authCode->setIdentifier('test-code');
        $authCode->setRevoked(true);
        $authCode->setExpiryDateTime(new DateTimeImmutable('+1 hour'));
        
        $this->repository->method('findOneBy')
            ->with(['identifier' => 'test-code'])
            ->willReturn($authCode);
        
        $result = $this->repository->isAuthCodeRevoked('test-code');
        
        $this->assertTrue($result);
    }

    public function testIsAuthCodeRevokedReturnsTrueWhenExpired()
    {
        $authCode = new AuthCode();
        $authCode->setIdentifier('test-code');
        $authCode->setRevoked(false);
        $authCode->setExpiryDateTime(new DateTimeImmutable('-1 hour'));
        
        $this->repository->method('findOneBy')
            ->with(['identifier' => 'test-code'])
            ->willReturn($authCode);
        
        $result = $this->repository->isAuthCodeRevoked('test-code');
        
        $this->assertTrue($result);
    }

    public function testIsAuthCodeRevokedReturnsFalseWhenValid()
    {
        $authCode = new AuthCode();
        $authCode->setIdentifier('test-code');
        $authCode->setRevoked(false);
        $authCode->setExpiryDateTime(new DateTimeImmutable('+1 hour'));
        
        $this->repository->method('findOneBy')
            ->with(['identifier' => 'test-code'])
            ->willReturn($authCode);
        
        $result = $this->repository->isAuthCodeRevoked('test-code');
        
        $this->assertFalse($result);
    }
}
