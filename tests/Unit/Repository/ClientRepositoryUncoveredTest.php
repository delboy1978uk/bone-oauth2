<?php

namespace Bone\OAuth2\Test\Unit\Repository;

use Bone\OAuth2\Repository\ClientRepository;
use Codeception\Test\Unit;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use ReflectionClass;

class ClientRepositoryUncoveredTest extends Unit
{
    public function testGetEntityManager()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $classMetadata = $this->getMockBuilder(ClassMetadata::class)->disableOriginalConstructor()->getMock();
        $entityName = 'Bone\\OAuth2\\Entity\\Client';
        $classMetadata->name = $entityName;

        $reflection = new ReflectionClass(ClientRepository::class);
        $repository = $reflection->newInstanceWithoutConstructor();
        
        $reflection = new ReflectionClass($repository);
        $emProperty = $reflection->getParentClass();
        while ($emProperty && !$emProperty->hasProperty('em')) {
            $emProperty = $emProperty->getParentClass();
        }
        if ($emProperty) {
            $p = $emProperty->getProperty('em');
            $p->setAccessible(true);
            $p->setValue($repository, $em);

            $p = $emProperty->getProperty('entityName');
            $p->setAccessible(true);
            $p->setValue($repository, $entityName);

            $p = $emProperty->getProperty('class');
            $p->setAccessible(true);
            $p->setValue($repository, $classMetadata);
        }

        $result = $repository->getEntityManager();

        $this->assertInstanceOf(EntityManagerInterface::class, $result);
        $this->assertSame($em, $result);
    }
}
