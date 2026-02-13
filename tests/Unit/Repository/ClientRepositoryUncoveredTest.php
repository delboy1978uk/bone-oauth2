<?php

namespace Bone\OAuth2\Test\Unit\Repository;

use Bone\OAuth2\Repository\ClientRepository;
use Codeception\Test\Unit;
use Doctrine\ORM\EntityManagerInterface;

class ClientRepositoryUncoveredTest extends Unit
{
    public function testGetEntityManager()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $repository = new ClientRepository($em);
        
        $result = $repository->getEntityManager();
        
        $this->assertInstanceOf(EntityManagerInterface::class, $result);
        $this->assertSame($em, $result);
    }
}
