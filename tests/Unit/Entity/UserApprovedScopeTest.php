<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Entity\UserApprovedScope;
use Codeception\Test\Unit;
use Del\Entity\User;

class UserApprovedScopeTest extends Unit
{
    private UserApprovedScope $userApprovedScope;

    protected function _before()
    {
        $this->userApprovedScope = new UserApprovedScope();
    }

    public function testSetAndGetId()
    {
        $this->userApprovedScope->setId('123');
        $this->assertEquals('123', $this->userApprovedScope->getId());
    }

    public function testSetAndGetUser()
    {
        $user = $this->createMock(User::class);
        
        $this->userApprovedScope->setUser($user);
        $this->assertSame($user, $this->userApprovedScope->getUser());
    }

    public function testSetAndGetClient()
    {
        $client = new Client();
        $client->setIdentifier('test-client');
        
        $this->userApprovedScope->setClient($client);
        $this->assertSame($client, $this->userApprovedScope->getClient());
    }

    public function testSetAndGetScope()
    {
        $scope = new Scope();
        $scope->setIdentifier('read');
        
        $this->userApprovedScope->setScope($scope);
        $this->assertSame($scope, $this->userApprovedScope->getScope());
    }

    public function testIdConversionToString()
    {
        $this->userApprovedScope->setId('456');
        $id = $this->userApprovedScope->getId();
        $this->assertIsString($id);
        $this->assertEquals('456', $id);
    }
}
