<?php

declare(strict_types=1);

namespace Bone\OAuth2\Fixtures;

use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\Scope;
use Del\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LoadClients implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $basicScope = $manager->getRepository(Scope::class)->findOneBy(['identifier' => 'basic']);
        $superuser = $manager->getRepository(User::class)->findOneBy(['email' => 'man@work.com']);

        $entity = new Client();
        $entity->setName('Bone Native Client');
        $entity->setDescription('Client used in Bone React Native Project');
        $entity->setIcon('https://raw.githubusercontent.com/boneframework/skeleton/refs/heads/master/public/img/skull_and_crossbones.png');
        $entity->setGrantType('auth_code');
        $entity->setRedirectUri('exp://192.168.0.204:8081/--/oauth2/callback');
        $entity->setIdentifier(\md5($entity->getName()));
        $time = \microtime();
        $name = $entity->getName();
        $secret = \password_hash($name . $time  . 'bone', PASSWORD_BCRYPT);
        $base64 = \base64_encode($secret);
        $entity->setSecret($base64);
        $entity->setConfidential(false);
        $entity->setProprietary(true);
        $entity->setScopes(new ArrayCollection([$basicScope]));
        $manager->persist($entity);
        $manager->flush();

        $entity = new Client();
        $entity->setName('API Docs');
        $entity->setDescription('Swagger API Docs client');
        $entity->setIcon('https://boneframework.docker/img/skull_and_crossbones.png');
        $entity->setGrantType('client_credentials');
        $entity->setRedirectUri('https://boneframework.docker/api/docs');
        $entity->setIdentifier(\md5($entity->getName()));
        $time = \microtime();
        $name = $entity->getName();
        $secret = \password_hash($name . $time  . 'bone', PASSWORD_BCRYPT);
        $base64 = \base64_encode($secret);
        $entity->setSecret($base64);
        $entity->setConfidential(true);
        $entity->setProprietary(true);
        $entity->setScopes(new ArrayCollection([$basicScope]));
        $entity->setUser($superuser);
        $manager->persist($entity);
        $manager->flush();

        $entity = new Client();
        $entity->setName('Third Party Website');
        $entity->setDescription('Connects to the API');
        $entity->setIcon('https://boneframework.docker/img/skull_and_crossbones.png');
        $entity->setGrantType('auth_code');
        $entity->setRedirectUri('https://awesome.scot/user/login/via/boneframework');
        $entity->setIdentifier(\md5($entity->getName()));
        $time = \microtime();
        $name = $entity->getName();
        $secret = \password_hash($name . $time  . 'bone', PASSWORD_BCRYPT);
        $base64 = \base64_encode($secret);
        $entity->setSecret($base64);
        $entity->setConfidential(false);
        $entity->setProprietary(false);
        $entity->setScopes(new ArrayCollection([$basicScope]));
        $manager->persist($entity);
        $manager->flush();
    }
}
