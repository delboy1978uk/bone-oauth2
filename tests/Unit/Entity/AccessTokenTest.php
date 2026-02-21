<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use Bone\OAuth2\Entity\AccessToken;
use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\Scope;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;

class AccessTokenTest extends Unit
{
    private AccessToken $accessToken;

    protected function _before()
    {
        $this->accessToken = new AccessToken();
    }

    public function testSetAndGetId()
    {
        $this->accessToken->setId(456);
        $this->assertEquals(456, $this->accessToken->getId());
    }

    public function testSetAndGetIdentifier()
    {
        $identifier = 'test-token-identifier';
        $this->accessToken->setIdentifier($identifier);
        $this->assertEquals($identifier, $this->accessToken->getIdentifier());
    }

    public function testSetAndGetExpiryDateTime()
    {
        $expiryDate = new DateTimeImmutable('2026-12-31 23:59:59');
        $this->accessToken->setExpiryDateTime($expiryDate);
        $this->assertEquals($expiryDate, $this->accessToken->getExpiryDateTime());
    }

    public function testSetAndGetUserIdentifier()
    {
        $this->accessToken->setUserIdentifier(789);
        $this->assertEquals(789, $this->accessToken->getUserIdentifier());
    }

    public function testUserIdentifierCanBeNull()
    {
        $this->assertNull($this->accessToken->getUserIdentifier());
    }

    public function testSetAndGetClient()
    {
        $client = new Client();
        $client->setIdentifier('test-client');
        
        $this->accessToken->setClient($client);
        $this->assertSame($client, $this->accessToken->getClient());
    }

    public function testAddScope()
    {
        $scope = new Scope();
        $scope->setIdentifier('read');
        
        $result = $this->accessToken->addScope($scope);
        
        $this->assertSame($this->accessToken, $result);
        $this->assertCount(1, $this->accessToken->getScopes());
        $this->assertContains($scope, $this->accessToken->getScopes());
    }

    public function testGetScopes()
    {
        $scope1 = new Scope();
        $scope1->setIdentifier('read');
        
        $scope2 = new Scope();
        $scope2->setIdentifier('write');
        
        $this->accessToken->addScope($scope1);
        $this->accessToken->addScope($scope2);
        
        $scopes = $this->accessToken->getScopes();
        $this->assertIsArray($scopes);
        $this->assertCount(2, $scopes);
    }

    public function testSetScopes()
    {
        $scope1 = new Scope();
        $scope1->setIdentifier('read');
        
        $scope2 = new Scope();
        $scope2->setIdentifier('write');
        
        $scopes = new ArrayCollection([$scope1, $scope2]);
        $this->accessToken->setScopes($scopes);
        
        $retrievedScopes = $this->accessToken->getScopes();
        $this->assertCount(2, $retrievedScopes);
    }

    public function testIsRevoked()
    {
        $this->assertFalse($this->accessToken->isRevoked());
        
        $this->accessToken->setRevoked(true);
        $this->assertTrue($this->accessToken->isRevoked());
        
        $this->accessToken->setRevoked(false);
        $this->assertFalse($this->accessToken->isRevoked());
    }

    public function testConstructorInitializesScopes()
    {
        $token = new AccessToken();
        $this->assertIsArray($token->getScopes());
        $this->assertCount(0, $token->getScopes());
    }

    public function testIdentifierDefaultsToEmptyString()
    {
        $token = new AccessToken();
        $this->assertEquals('', $token->getIdentifier());
    }

    public function testRevokedDefaultsToFalse()
    {
        $token = new AccessToken();
        $this->assertFalse($token->isRevoked());
    }
}
