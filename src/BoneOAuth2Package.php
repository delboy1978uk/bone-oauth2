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
use Bone\OAuth2\Repository\AccessTokenRepository;
use Bone\OAuth2\Repository\AuthCodeRepository;
use Bone\OAuth2\Repository\ClientRepository;
use Bone\OAuth2\Repository\RefreshTokenRepository;
use Bone\OAuth2\Repository\ScopeRepository;
use Bone\OAuth2\Repository\UserRepository;
use Bone\OAuth2\Service\ClientService;
use Doctrine\ORM\EntityManager;

class BoneOAuth2Package implements RegistrationInterface
{
    /**
     * @param Container $c
     */
    public function addToContainer(Container $c)
    {
        // AccessToken
        $function = function (Container $c) {
            /** @var EntityManager $entityManager */
            $entityManager = $c->get(EntityManager::class);

            return $entityManager->getRepository(AccessToken::class);
        };
        $c[AccessTokenRepository::class] = $c->factory($function);

        // AuthCode
        $function = function (Container $c) {
            /** @var EntityManager $entityManager */
            $entityManager = $c->get(EntityManager::class);

            return $entityManager->getRepository(AuthCode::class);
        };
        $c[AuthCodeRepository::class] = $c->factory($function);

        // Client
        $function = function (Container $c) {
            /** @var EntityManager $entityManager */
            $entityManager = $c->get(EntityManager::class);

            return $entityManager->getRepository(Client::class);
        };
        $c[ClientRepository::class] = $c->factory($function);

        $function = function (Container $c) {
            $repository = $c->get(ClientRepository::class);

            return new ClientService($repository);
        };
        $c[ClientService::class] = $c->factory($function);

        // RefreshToken
        $function = function (Container $c) {
            /** @var EntityManager $entityManager */
            $entityManager = $c->get(EntityManager::class);

            return $entityManager->getRepository(RefreshToken::class);
        };
        $c[RefreshTokenRepository::class] = $c->factory($function);

        // Scope
        $function = function (Container $c) {
            /** @var EntityManager $entityManager */
            $entityManager = $c->get(EntityManager::class);

            return $entityManager->getRepository(Scope::class);
        };
        $c[ScopeRepository::class] = $c->factory($function);

        // User
        $function = function (Container $c) {
            /** @var EntityManager $entityManager */
            $entityManager = $c->get(EntityManager::class);

            return $entityManager->getRepository(OAuthUser::class);
        };
        $c[UserRepository::class] = $c->factory($function);
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
