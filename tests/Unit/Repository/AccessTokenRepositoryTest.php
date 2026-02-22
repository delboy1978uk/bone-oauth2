<?php

declare(strict_types=1);

namespace Tests\Unit\Repository;

use Bone\OAuth2\Entity\AccessToken;
use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Exception\OAuthException;
use Bone\OAuth2\Repository\AccessTokenRepository;
use Codeception\Test\Unit;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;

class AccessTokenRepositoryTest extends Unit
{
    private AccessTokenRepository $repository;
    private EntityManagerInterface $entityManager;

    protected function _before()
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        // Create repository with mocked EntityManager
        $this->repository = $this->getMockBuilder(AccessTokenRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntityManager', 'findOneBy'])
            ->getMock();
            
        $this->repository->method('getEntityManager')
            ->willReturn($this->entityManager);
    }

    public function testPersistNewAccessToken()
    {
        $accessToken = new AccessToken();
        $accessToken->setIdentifier('test-token');
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($accessToken);
            
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $this->repository->persistNewAccessToken($accessToken);
    }

    public function testRevokeAccessToken()
    {
        $accessToken = new AccessToken();
        $accessToken->setIdentifier('test-token');
        $accessToken->setRevoked(false);
        
        $this->repository->method('findOneBy')
            ->with(['identifier' => 'test-token'])
            ->willReturn($accessToken);
            
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $this->repository->revokeAccessToken('test-token');
        
        $this->assertTrue($accessToken->isRevoked());
    }

    public function testRevokeAccessTokenThrowsExceptionWhenNotFound()
    {
        $this->repository->method('findOneBy')
            ->with(['identifier' => 'non-existent'])
            ->willReturn(null);
        
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Token not found');
        $this->expectExceptionCode(404);
        
        $this->repository->revokeAccessToken('non-existent');
    }

    public function testIsAccessTokenRevokedReturnsTrueWhenTokenNotFound()
    {
        $this->repository->method('findOneBy')
            ->with(['identifier' => 'non-existent'])
            ->willReturn(null);
        
        $result = $this->repository->isAccessTokenRevoked('non-existent');
        
        $this->assertTrue($result);
    }

    public function testIsAccessTokenRevokedReturnsTrueWhenRevoked()
    {
        $accessToken = new AccessToken();
        $accessToken->setIdentifier('test-token');
        $accessToken->setRevoked(true);
        
        $this->repository->method('findOneBy')
            ->with(['identifier' => 'test-token'])
            ->willReturn($accessToken);
        
        $result = $this->repository->isAccessTokenRevoked('test-token');
        
        $this->assertTrue($result);
    }

    public function testIsAccessTokenRevokedReturnsFalseWhenNotRevoked()
    {
        $accessToken = new AccessToken();
        $accessToken->setIdentifier('test-token');
        $accessToken->setRevoked(false);
        
        $this->repository->method('findOneBy')
            ->with(['identifier' => 'test-token'])
            ->willReturn($accessToken);
        
        $result = $this->repository->isAccessTokenRevoked('test-token');
        
        $this->assertFalse($result);
    }

    public function testGetNewToken()
    {
        $client = new Client();
        $client->setIdentifier('test-client');
        
        $scope1 = new Scope();
        $scope1->setIdentifier('read');
        
        $scope2 = new Scope();
        $scope2->setIdentifier('write');
        
        $scopes = [$scope1, $scope2];
        $userIdentifier = 123;
        
        $token = $this->repository->getNewToken($client, $scopes, $userIdentifier);
        
        $this->assertInstanceOf(AccessToken::class, $token);
        $this->assertSame($client, $token->getClient());
        $this->assertEquals($userIdentifier, $token->getUserIdentifier());
        $this->assertCount(2, $token->getScopes());
    }

    public function testGetNewTokenWithoutUserIdentifier()
    {
        $client = new Client();
        $client->setIdentifier('test-client');
        
        $scopes = [];
        
        $token = $this->repository->getNewToken($client, $scopes);
        
        $this->assertInstanceOf(AccessToken::class, $token);
        $this->assertSame($client, $token->getClient());
        $this->assertNull($token->getUserIdentifier());
    }
}
