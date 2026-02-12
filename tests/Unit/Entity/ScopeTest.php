<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use Bone\OAuth2\Entity\Scope;
use Codeception\Test\Unit;

class ScopeTest extends Unit
{
    private Scope $scope;

    protected function _before()
    {
        $this->scope = new Scope();
    }

    public function testSetAndGetIdentifier()
    {
        $this->scope->setIdentifier('read');
        $this->assertEquals('read', $this->scope->getIdentifier());
    }

    public function testSetAndGetDescription()
    {
        $description = 'Read access to resources';
        $this->scope->setDescription($description);
        $this->assertEquals($description, $this->scope->getDescription());
    }

    public function testGetId()
    {
        // ID is null initially as it's set by Doctrine
        $this->assertNull($this->scope->getId());
    }

    public function testJsonSerialize()
    {
        $this->scope->setIdentifier('write');
        $this->assertEquals('write', $this->scope->jsonSerialize());
    }
}
