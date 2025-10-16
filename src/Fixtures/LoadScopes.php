<?php

declare(strict_types=1);

namespace Bone\OAuth2\Fixtures;

use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\Scope;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LoadScopes implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $entity = new Scope();
        $entity->setIdentifier('basic');
        $entity->setDescription('Name, email, and profile picture.');
        $manager->persist($entity);
        $manager->flush();
    }
}
