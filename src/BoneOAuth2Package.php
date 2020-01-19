<?php

declare(strict_types=1);

namespace Bone\OAuth2;

use Barnacle\Container;
use Barnacle\RegistrationInterface;
use Bone\OAuth2\Entity\AccessToken;
use Bone\OAuth2\Entity\AuthCode;
use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\OAuthUser;
use Bone\OAuth2\Entity\RefreshToken;
use Bone\OAuth2\Entity\Scope;
use Doctrine\ORM\EntityManager;

class BoneOAuth2Package implements RegistrationInterface
{
    /**
     * @param Container $c
     */
    public function addToContainer(Container $c)
    {
        // AccessToken
        $function = function ($c) {
            /** @var EntityManager $entityManager */
            $entityManager = $c['doctrine.entity_manager'];

            return $entityManager->getRepository(AccessToken::class);
        };
        $c['repository.AccessToken'] = $c->factory($function);

        // AuthCode
        $function = function ($c) {
            /** @var EntityManager $entityManager */
            $entityManager = $c['doctrine.entity_manager'];

            return $entityManager->getRepository(AuthCode::class);
        };
        $c['repository.AuthCode'] = $c->factory($function);

        // Client
        $function = function ($c) {
            /** @var EntityManager $entityManager */
            $entityManager = $c['doctrine.entity_manager'];

            return $entityManager->getRepository(Client::class);
        };
        $c['repository.Client'] = $c->factory($function);

        $function = function ($c) {
            $repository = $c['repository.Client'];

            return new ClientService($repository);
        };
        $c['oauth.service.client'] = $c->factory($function);

        // RefreshToken
        $function = function ($c) {
            /** @var EntityManager $entityManager */
            $entityManager = $c['doctrine.entity_manager'];

            return $entityManager->getRepository(RefreshToken::class);
        };
        $c['repository.RefreshToken'] = $c->factory($function);

        // Scope
        $function = function ($c) {
            /** @var EntityManager $entityManager */
            $entityManager = $c['doctrine.entity_manager'];

            return $entityManager->getRepository(Scope::class);
        };
        $c['repository.Scope'] = $c->factory($function);

        // User
        $function = function ($c) {
            /** @var EntityManager $entityManager */
            $entityManager = $c['doctrine.entity_manager'];

            return $entityManager->getRepository(OAuthUser::class);
        };
        $c['repository.User'] = $c->factory($function);
    }

    /**
     * @return string
     */
    public function getEntityPath(): string
    {
        return __DIR__ . '/Entity';
    }

    /**
     * @return bool
     */
    public function hasEntityPath(): bool
    {
        return true;
    }
}
