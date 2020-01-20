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
use DateInterval;
use Doctrine\ORM\EntityManager;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\Middleware\AuthorizationServerMiddleware;
use League\OAuth2\Server\Middleware\ResourceServerMiddleware;
use League\OAuth2\Server\ResourceServer;

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

        // OAuth2 Server
        $function = function (Container $c) {
            $clientRepository = $c->get(ClientRepository::class);
            $accessTokenRepository = $c->get(AccessTokenRepository::class);
            $scopeRepository = $c->get(ScopeRepository::class);
            $authCodeRepository = $c->get(AuthCodeRepository::class);
            $refreshTokenRepository = $c->get(RefreshTokenRepository::class);
            $privateKeyPath = $c->get('oauth2')['privateKeyPath'];
            $encryptionKey = $c->get('oauth2')['encryptionKey'];

            // Setup the authorization server
            $server = new AuthorizationServer(
                $clientRepository,
                $accessTokenRepository,
                $scopeRepository,
                $privateKeyPath,
                $encryptionKey
            );

            // Enable the authentication code grant on the server with a token TTL of 1 hour
            $server->enableGrantType(
                new AuthCodeGrant(
                    $authCodeRepository,
                    $refreshTokenRepository,
                    new DateInterval('PT10M')
                ),
                new DateInterval('PT1H')
            );

            // Enable the refresh token grant on the server with a token TTL of 1 month
            $server->enableGrantType(
                new RefreshTokenGrant($refreshTokenRepository),
                new DateInterval('P1M')
            );

            return $server;
        };
        $c[AuthorizationServer::class] = $c->factory($function);

        // Resource Server
        $function = function (Container $c) {
            $publicKeyPath = $c->get('oauth2')['publicKeyPath'];
            $accessTokenRepository = $c->get(AccessTokenRepository::class);

            $server = new ResourceServer(
                $accessTokenRepository,
                $publicKeyPath
            );

            return $server;
        };
        $c[ResourceServer::class] = $c->factory($function);

        // Auth Server Middleware
        $c[AuthorizationServerMiddleware::class] = new AuthorizationServerMiddleware($c->get(AuthorizationServer::class));

        // Resource Server Middleware
        $c[ResourceServerMiddleware::class] = new ResourceServerMiddleware($c->get(AuthorizationServer::class));

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
