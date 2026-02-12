<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use Bone\OAuth2\Entity\AuthCode;
use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\Scope;
use Codeception\Test\Unit;
use DateTimeImmutable;

class AuthCodeTest extends Unit
{
    private AuthCode $authCode;

    protected function _before()
    {
        $this->authCode = new AuthCode();
    }

    public function testSetAndGetIdentifier()
    {
        $identifier = 'test-auth-code-identifier';
        $this->authCode->setIdentifier($identifier);
        $this->assertEquals($identifier, $this->authCode->getIdentifier());
    }

    public function testSetAndGetExpiryDateTime()
    {
        $expiryDate = new DateTimeImmutable('2026-12-31 23:59:59');
        $this->authCode->setExpiryDateTime($expiryDate);
        $this->assertEquals($expiryDate, $this->authCode->getExpiryDateTime());
    }

    public function testSetAndGetUserIdentifier()
    {
        $this->authCode->setUserIdentifier(123);
        $this->assertEquals(123, $this->authCode->getUserIdentifier());
    }

    public function testSetAndGetClient()
    {
        $client = new Client();
        $client->setIdentifier('test-client');
        
        $this->authCode->setClient($client);
        $this->assertSame($client, $this->authCode->getClient());
    }

    public function testSetAndGetRedirectUri()
    {
        $uri = 'https://example.com/callback';
        $this->authCode->setRedirectUri($uri);
        $this->assertEquals($uri, $this->authCode->getRedirectUri());
    }

    public function testAddScope()
    {
        $scope = new Scope();
        $scope->setIdentifier('read');
        
        $this->authCode->addScope($scope);
        
        $scopes = $this->authCode->getScopes();
        $this->assertCount(1, $scopes);
        $this->assertContains($scope, $scopes);
    }

    public function testGetScopes()
    {
        $scope1 = new Scope();
        $scope1->setIdentifier('read');
        
        $scope2 = new Scope();
        $scope2->setIdentifier('write');
        
        $this->authCode->addScope($scope1);
        $this->authCode->addScope($scope2);
        
        $scopes = $this->authCode->getScopes();
        $this->assertIsArray($scopes);
        $this->assertCount(2, $scopes);
    }

    public function testIsRevoked()
    {
        $this->assertFalse($this->authCode->isRevoked());
        
        $this->authCode->setRevoked(true);
        $this->assertTrue($this->authCode->isRevoked());
        
        $this->authCode->setRevoked(false);
        $this->assertFalse($this->authCode->isRevoked());
    }

    public function testConstructorInitializesScopes()
    {
        $authCode = new AuthCode();
        $this->assertIsArray($authCode->getScopes());
        $this->assertCount(0, $authCode->getScopes());
    }

    public function testRevokedDefaultsToFalse()
    {
        $authCode = new AuthCode();
        $this->assertFalse($authCode->isRevoked());
    }
}
