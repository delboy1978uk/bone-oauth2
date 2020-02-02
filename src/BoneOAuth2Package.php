<?php

declare(strict_types=1);

namespace Bone\OAuth2;

use Barnacle\Container;
use Barnacle\RegistrationInterface;
use Bone\Mvc\Controller\Init;
use Bone\Mvc\Router\RouterConfigInterface;
use Bone\Mvc\View\PlatesEngine;
use Bone\OAuth2\Controller\ApiKeyController;
use Bone\OAuth2\Controller\AuthServerController;
use Bone\OAuth2\Controller\ExampleController;
use Bone\OAuth2\Entity\AccessToken;
use Bone\OAuth2\Entity\AuthCode;
use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\OAuthUser;
use Bone\OAuth2\Entity\RefreshToken;
use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Entity\UserApprovedScope;
use Bone\OAuth2\Http\Middleware\ResourceServerMiddleware;
use Bone\OAuth2\Repository\AccessTokenRepository;
use Bone\OAuth2\Repository\AuthCodeRepository;
use Bone\OAuth2\Repository\ClientRepository;
use Bone\OAuth2\Repository\RefreshTokenRepository;
use Bone\OAuth2\Repository\ScopeRepository;
use Bone\OAuth2\Repository\UserApprovedScopeRepository;
use Bone\OAuth2\Repository\UserRepository;
use Bone\OAuth2\Service\ClientService;
use Bone\OAuth2\Service\PermissionService;
use BoneMvc\Module\BoneMvcUser\Http\Middleware\SessionAuth;
use BoneMvc\Module\BoneMvcUser\Http\Middleware\SessionAuthRedirect;
use DateInterval;
use Del\Service\UserService;
use Doctrine\ORM\EntityManager;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResourceServer;
use League\Route\Router;
use Zend\Diactoros\ResponseFactory;

class BoneOAuth2Package implements RegistrationInterface, RouterConfigInterface
{
    /**
     * @param Container $c
     */
    public function addToContainer(Container $c)
    {
        /** @var PlatesEngine $viewEngine */
        $viewEngine = $c->get(PlatesEngine::class);
        $viewEngine->addFolder('boneoauth2', __DIR__ . '/View/');

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

        // User Approved Scopes
        $function = function (Container $c) {
            /** @var EntityManager $entityManager */
            $entityManager = $c->get(EntityManager::class);

            return $entityManager->getRepository(UserApprovedScope::class);
        };
        $c[UserApprovedScopeRepository::class] = $c->factory($function);

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

            // Enable the client credentials grant on the server with a token TTL of 1 hour
            $server->enableGrantType(
                new ClientCredentialsGrant(),
                new DateInterval('PT1H')
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

        // Permissions Service
        $function = function (Container $c) {
            /** @var UserApprovedScopeRepository $repository */
            $repository = $c->get(UserApprovedScopeRepository::class);

            return new PermissionService($repository);
        };
        $c[PermissionService::class] = $c->factory($function);

        // AuthServerController::
        $c[AuthServerController::class] = $c->factory(function (Container $c) {
            $authServer = $c->get(AuthorizationServer::class);
            $userService = $c->get(UserService::class);
            $permissionService = $c->get(PermissionService::class);
            $controller = new AuthServerController($authServer, $userService, $permissionService);

            return Init::controller($controller, $c);
        });

        // AuthServerController::
        $c[ResourceServerMiddleware::class] = $c->factory(function (Container $c) {
            return new ResourceServerMiddleware($c->get(ResourceServer::class), $c->get(UserService::class), new ResponseFactory());
        });

        // AuthServerController::
        $c[ApiKeyController::class] = $c->factory(function (Container $c) {
            return Init::controller(new ApiKeyController($c->get(ClientService::class)), $c);
        });
    }

    /**
     * @param Container $c
     * @param Router $router
     */
    public function addRoutes(Container $c, Router $router)
    {
        $router->map('GET', '/oauth2/authorize', [AuthServerController::class, 'authorizeAction'])
        ->middleware($c->get(SessionAuthRedirect::class));
        $router->map('POST', '/oauth2/authorize', [AuthServerController::class, 'authorizeAction'])
            ->middleware($c->get(SessionAuthRedirect::class));
        $router->map('POST', '/oauth2/token', [AuthServerController::class, 'accessTokenAction']);
        $router->map('GET', '/oauth2/callback', [ExampleController::class, 'callbackAction']);
        $router->map('GET', '/ping', [ExampleController::class, 'pingAction'])->middleware($c->get(ResourceServerMiddleware::class));
        $router->map('GET', '/user/api-keys', [ApiKeyController::class, 'myApiKeysAction'])
        ->middleware($c->get(SessionAuth::class));
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
