<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\Scope;
use Codeception\Test\Unit;
use Del\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;

class ClientTest extends Unit
{
    private Client $client;

    protected function _before()
    {
        $this->client = new Client();
    }

    public function testSetAndGetId()
    {
        $this->client->setId(123);
        $this->assertEquals(123, $this->client->getId());
    }

    public function testSetAndGetName()
    {
        $this->client->setName('Test Client');
        $this->assertEquals('Test Client', $this->client->getName());
    }

    public function testSetAndGetIdentifier()
    {
        $this->client->setIdentifier('test-identifier');
        $this->assertEquals('test-identifier', $this->client->getIdentifier());
    }

    public function testSetAndGetSecret()
    {
        $this->client->setSecret('test-secret');
        $this->assertEquals('test-secret', $this->client->getSecret());
    }

    public function testSetAndGetRedirectUri()
    {
        $uri = 'https://example.com/callback';
        $this->client->setRedirectUri($uri);
        $this->assertEquals($uri, $this->client->getRedirectUri());
    }

    public function testSetAndGetDescription()
    {
        $description = 'Test client description';
        $this->client->setDescription($description);
        $this->assertEquals($description, $this->client->getDescription());
    }

    public function testSetAndGetIcon()
    {
        $icon = 'fa-icon-test';
        $this->client->setIcon($icon);
        $this->assertEquals($icon, $this->client->getIcon());
    }

    public function testSetAndGetGrantType()
    {
        $grantType = 'authorization_code';
        $this->client->setGrantType($grantType);
        $this->assertEquals($grantType, $this->client->getGrantType());
    }

    public function testIsConfidential()
    {
        $this->client->setConfidential(true);
        $this->assertTrue($this->client->isConfidential());
        
        $this->client->setConfidential(false);
        $this->assertFalse($this->client->isConfidential());
    }

    public function testIsProprietary()
    {
        $this->client->setProprietary(true);
        $this->assertTrue($this->client->isProprietary());
        
        $this->client->setProprietary(false);
        $this->assertFalse($this->client->isProprietary());
    }

    public function testSetAndGetUser()
    {
        $user = $this->createMock(User::class);
        $this->client->setUser($user);
        $this->assertSame($user, $this->client->getUser());
    }

    public function testScopesCollection()
    {
        $scope1 = new Scope();
        $scope1->setIdentifier('read');
        
        $scope2 = new Scope();
        $scope2->setIdentifier('write');
        
        $scopes = new ArrayCollection([$scope1, $scope2]);
        $this->client->setScopes($scopes);
        
        $retrievedScopes = $this->client->getScopes();
        $this->assertInstanceOf(ArrayCollection::class, $retrievedScopes);
        $this->assertCount(2, $retrievedScopes);
        $this->assertTrue($retrievedScopes->contains($scope1));
        $this->assertTrue($retrievedScopes->contains($scope2));
    }

    public function testConstructorInitializesScopes()
    {
        $client = new Client();
        $this->assertInstanceOf(ArrayCollection::class, $client->getScopes());
        $this->assertCount(0, $client->getScopes());
    }
}
