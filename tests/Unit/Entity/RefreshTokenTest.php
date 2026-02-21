<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use Bone\OAuth2\Entity\AccessToken;
use Bone\OAuth2\Entity\RefreshToken;
use Codeception\Test\Unit;
use DateTimeImmutable;

class RefreshTokenTest extends Unit
{
    private RefreshToken $refreshToken;

    protected function _before()
    {
        $this->refreshToken = new RefreshToken();
    }

    public function testSetAndGetIdentifier()
    {
        $identifier = 'test-refresh-token-identifier';
        $this->refreshToken->setIdentifier($identifier);
        $this->assertEquals($identifier, $this->refreshToken->getIdentifier());
    }

    public function testSetAndGetAccessToken()
    {
        $accessToken = new AccessToken();
        $accessToken->setIdentifier('test-access-token');
        
        $this->refreshToken->setAccessToken($accessToken);
        $this->assertSame($accessToken, $this->refreshToken->getAccessToken());
    }

    public function testSetAndGetExpiryDateTime()
    {
        $expiryDate = new DateTimeImmutable('2026-12-31 23:59:59');
        $this->refreshToken->setExpiryDateTime($expiryDate);
        $this->assertEquals($expiryDate, $this->refreshToken->getExpiryDateTime());
    }

    public function testIsRevoked()
    {
        $this->assertFalse($this->refreshToken->isRevoked());
        
        $this->refreshToken->setRevoked(true);
        $this->assertTrue($this->refreshToken->isRevoked());
        
        $this->refreshToken->setRevoked(false);
        $this->assertFalse($this->refreshToken->isRevoked());
    }

    public function testGetId()
    {
        // ID is null initially as it's set by Doctrine
        $this->assertNull($this->refreshToken->getId());
    }

    public function testRevokedDefaultsToFalse()
    {
        $refreshToken = new RefreshToken();
        $this->assertFalse($refreshToken->isRevoked());
    }
}
