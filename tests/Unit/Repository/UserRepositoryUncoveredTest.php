<?php

namespace Bone\OAuth2\Test\Unit\Repository;

use Bone\OAuth2\Repository\UserRepository;
use Del\Entity\User;
use Del\Person\Entity\Person;
use Codeception\Test\Unit;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use ReflectionClass;
use Doctrine\ORM\EntityRepository;

class UserRepositoryUncoveredTest extends Unit
{
    public function testGetUserDetailsWithValidUser()
    {
        $user = new User();
        $user->setId(1);
        $user->setEmail('test@example.com');
        $person = $this->createMock(Person::class);
        $user->setPerson($person);

        $repository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findOneBy'])
            ->getMock();

        $repository->method('findOneBy')
            ->with(['email' => 'test@example.com'])
            ->willReturn($user);

        $result = $repository->getUserDetails('test@example.com');

        $this->assertIsArray($result);
        $this->assertEquals('test@example.com', $result['email']);
    }

    public function testGetUserDetailsWithInvalidUser()
    {
        $repository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findOneBy'])
            ->getMock();

        $repository->method('findOneBy')
            ->with(['email' => 'nonexistent@example.com'])
            ->willReturn(null);

        $result = $repository->getUserDetails('nonexistent@example.com');

        $this->assertNull($result);
    }
}
