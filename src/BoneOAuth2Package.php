<?php

declare(strict_types=1);

namespace Bone\OAuth2;

use Barnacle\Container;
use Barnacle\RegistrationInterface;
use Bone\Console\Command;
use Bone\Console\CommandRegistrationInterface;
use Bone\Contracts\Container\DefaultSettingsProviderInterface;
use Bone\Contracts\Container\DependentPackagesProviderInterface;
use Bone\Contracts\Container\EntityRegistrationInterface;
use Bone\Contracts\Container\FixtureProviderInterface;
use Bone\Contracts\Container\PostInstallProviderInterface;
use Bone\Controller\Init;
use Bone\Http\Middleware\JsonParse;
use Bone\OAuth2\Command\ClientCommand;
use Bone\OAuth2\Command\ClientScopeCommand;
use Bone\OAuth2\Command\ScopeCreateCommand;
use Bone\OAuth2\Command\ScopeListCommand;
use Bone\OAuth2\Fixtures\LoadClients;
use Bone\OAuth2\Fixtures\LoadScopes;
use Bone\OAuth2\Http\Middleware\AuthServerMiddleware;
use Bone\OAuth2\Http\Middleware\ScopeCheck;
use Bone\Router\RouterConfigInterface;
use Bone\View\ViewEngine;
use Bone\OAuth2\Controller\ApiKeyController;
use Bone\OAuth2\Controller\AuthServerController;
use Bone\OAuth2\Controller\ExampleController;
use Bone\OAuth2\Entity\AccessToken;
use Bone\OAuth2\Entity\AuthCode;
use Bone\OAuth2\Entity\Client;
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
use Bone\OAuth2\Service\ClientService;
use Bone\OAuth2\Service\PermissionService;
use Bone\User\Http\Middleware\SessionAuth;
use Bone\User\Http\Middleware\SessionAuthRedirect;
use Bone\Router\Router;
use Bone\View\ViewEngineInterface;
use DateInterval;
use Del\Service\UserService;
use Del\SessionManager;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResourceServer;
use Symfony\Component\Console\Style\SymfonyStyle;

use function file_exists;

class BoneOAuth2Package implements RegistrationInterface, RouterConfigInterface, CommandRegistrationInterface,
                                   EntityRegistrationInterface, FixtureProviderInterface, DefaultSettingsProviderInterface,
                                   DependentPackagesProviderInterface, PostInstallProviderInterface
{
    public function addToContainer(Container $c): void
    {
        $viewEngine = $c->get(ViewEngine::class);
        $viewEngine->addFolder('boneoauth2', __DIR__ . '/View/');

        // AccessToken
        $function = function (Container $c) {
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $c->get(EntityManagerInterface::class);

            return $entityManager->getRepository(AccessToken::class);
        };
        $c[AccessTokenRepository::class] = $c->factory($function);

        // AuthCode
        $function = function (Container $c) {
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $c->get(EntityManagerInterface::class);

            return $entityManager->getRepository(AuthCode::class);
        };
        $c[AuthCodeRepository::class] = $c->factory($function);

        // Client
        $function = function (Container $c) {
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $c->get(EntityManagerInterface::class);

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
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $c->get(EntityManagerInterface::class);

            return $entityManager->getRepository(RefreshToken::class);
        };
        $c[RefreshTokenRepository::class] = $c->factory($function);

        // Scope
        $function = function (Container $c) {
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $c->get(EntityManagerInterface::class);

            return $entityManager->getRepository(Scope::class);
        };
        $c[ScopeRepository::class] = $c->factory($function);

        // User Approved Scopes
        $function = function (Container $c) {
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $c->get(EntityManagerInterface::class);

            return $entityManager->getRepository(UserApprovedScope::class);
        };
        $c[UserApprovedScopeRepository::class] = $c->factory($function);

        // OAuth2 Server
        $function = function (Container $c) {
            $settings = $c->get('oauth2');
            $clientRepository = $c->get(ClientRepository::class);
            $accessTokenRepository = $c->get(AccessTokenRepository::class);
            $scopeRepository = $c->get(ScopeRepository::class);
            $authCodeRepository = $c->get(AuthCodeRepository::class);
            $refreshTokenRepository = $c->get(RefreshTokenRepository::class);
            $privateKeyPath = $settings['privateKeyPath'];
            $encryptionKey = $settings['encryptionKey'];

            $server = new AuthorizationServer(
                $clientRepository,
                $accessTokenRepository,
                $scopeRepository,
                $privateKeyPath,
                $encryptionKey
            );

            $clientCredentialsTokenTTL = $settings['clientCredentialsTokenTTL'] ?: 'PT1H';
            $authCodeTTL = $settings['authCodeTTL'] ?: 'PT1M';
            $accessTokenTTL = $settings['accessTokenTTL'] ?: 'PT5M';
            $refreshTokenTTL = $settings['refreshTokenTTL'] ?: 'P1M';

            $server->enableGrantType(
                new ClientCredentialsGrant(),
                new DateInterval($clientCredentialsTokenTTL)
            );

            $server->enableGrantType(
                new AuthCodeGrant(
                    $authCodeRepository,
                    $refreshTokenRepository,
                    new DateInterval($authCodeTTL)
                ),
                new DateInterval($accessTokenTTL)
            );

            $refreshGrant = new RefreshTokenGrant($refreshTokenRepository);
            $refreshGrant->setRefreshTokenTTL(new DateInterval($refreshTokenTTL));
            $server->enableGrantType(
                $refreshGrant,
                new DateInterval($accessTokenTTL)
            );

            return $server;
        };
        $c[AuthorizationServer::class] = $c->factory($function);

        // Resource Server
        $function = function (Container $c) {
            $publicKeyPath = $c->get('oauth2')['publicKeyPath'];

            if (!file_exists($publicKeyPath)) {
                throw new \Exception("Key not found. Create one! In `$publicKeyPath`, run:\nopenssl genrsa -out private.key 2048\nopenssl rsa -in private.key -pubout -out public.key\nchmod 660 public.key\nchmod 660 private.key\n");
            }

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

        $c[AuthServerController::class] = $c->factory(function (Container $c) {
            $authServer = $c->get(AuthorizationServer::class);
            $userService = $c->get(UserService::class);
            $permissionService = $c->get(PermissionService::class);
            $clientService = $c->get(ClientService::class);
            $controller = new AuthServerController($authServer, $userService, $permissionService, $clientService);

            return Init::controller($controller, $c);
        });

        $c[AuthServerMiddleware::class] = $c->factory(function (Container $c) {
            $userService = $c->get(UserService::class);
            $view = $c->get(ViewEngineInterface::class);
            $authServer = $c->get(AuthorizationServer::class);
            $permissionService = $c->get(PermissionService::class);

            return new AuthServerMiddleware($userService, $view, SessionManager::getInstance(), $authServer, $permissionService);
        });

        $c[ResourceServerMiddleware::class] = $c->factory(function (Container $c) {
            return new ResourceServerMiddleware($c->get(ResourceServer::class), $c->get(UserService::class), $c->get(ClientService::class));
        });

        $c[ApiKeyController::class] = $c->factory(function (Container $c) {
            return Init::controller(new ApiKeyController($c->get(ClientService::class)), $c);
        });
    }

    public function addRoutes(Container $c, Router $router): void
    {
        $router->map('GET', '/oauth2/authorize', [AuthServerController::class, 'authorizeAction'])->middlewares([$c->get(SessionAuthRedirect::class), $c->get(AuthServerMiddleware::class)]);
        $router->map('POST', '/oauth2/authorize', [AuthServerController::class, 'authorizeAction'])->middlewares([$c->get(SessionAuthRedirect::class), $c->get(AuthServerMiddleware::class)]);
        $router->map('POST', '/oauth2/token', [AuthServerController::class, 'accessTokenAction']);
        $router->map('GET', '/oauth2/login', [AuthServerController::class, 'loginAsSomeoneElse']);
        $router->map('GET', '/oauth2/callback', [ExampleController::class, 'callbackAction']);
        $router->map('GET', '/ping', [ExampleController::class, 'pingAction']);
        $router->map('GET', '/user/api-keys', [ApiKeyController::class, 'myApiKeysAction'])->middleware($c->get(SessionAuth::class));
        $router->map('GET', '/user/api-keys/add', [ApiKeyController::class, 'addAction'])->middleware($c->get(SessionAuth::class));
        $router->map('POST', '/user/api-keys/add', [ApiKeyController::class, 'addSubmitAction'])->middleware($c->get(SessionAuth::class));
        $router->map('GET', '/user/api-keys/delete/{id:number}', [ApiKeyController::class, 'deleteConfirmAction'])->middleware($c->get(SessionAuth::class));
        $router->map('POST', '/user/api-keys/delete/{id:number}', [ApiKeyController::class, 'deleteAction'])->middleware($c->get(SessionAuth::class));
        $router->map('POST', '/oauth2/register', [AuthServerController::class, 'registerAction'])
            ->middlewares([$c->get(ResourceServerMiddleware::class), new ScopeCheck(['register']), new JsonParse()]);
    }

    public function getEntityPath(): string
    {
        return __DIR__ . '/Entity';
    }


    public function registerConsoleCommands(Container $container): array
    {
        $userService = $container[UserService::class];
        $clientService = $container->get(ClientService::class);
        $scopeRepository = $container->get(ScopeRepository::class);
        $clientCommand = new ClientCommand($clientService, $userService, $scopeRepository);
        $scopeCreateCommand = new ScopeCreateCommand($scopeRepository);
        $scopeListCommand = new ScopeListCommand($scopeRepository);
        $clientScopeCommand = new ClientScopeCommand($clientService, $scopeRepository);

        return [
            $clientCommand, $scopeCreateCommand, $scopeListCommand, $clientScopeCommand,
        ];
    }

    public function getFixtures(): array
    {
        return [
            LoadScopes::class,
            LoadClients::class
        ];
    }

    public function getSettingsFileName(): string
    {
        return __DIR__ . '/../data/config/bone-oauth2.php';
    }

    public function getRequiredPackages(): array
    {
        return [
            'Bone\Mail\MailPackage',
            'Bone\BoneDoctrine\BoneDoctrinePackage',
            'Bone\Paseto\PasetoPackage',
            'Del\Person\PersonPackage',
            'Del\UserPackage',
            'Bone\User\BoneUserPackage',
            self::class,
        ];
    }

    public function postInstall(Command $command, SymfonyStyle $io): void
    {
        $io->writeln('Checking for existing SSL keys..');

        if (file_exists('data/keys/private.key') && file_exists('data/keys/public.key')) {
            $io->warning('SSL keys already exist. Skipping.');
        } else {
            $io->writeln('No existing SSL keys found. Creating keys...');
            $command->runProcess($io, ['openssl', 'genrsa', '-out', 'private.key', '2048']);
            $command->runProcess($io, ['openssl', 'rsa', '-in', 'private.key', '-pubout', '-out', 'public.key']);
            $command->runProcess($io, ['mv', 'private.key', 'data/keys']);
            $command->runProcess($io, ['mv', 'public.key', 'data/keys']);
            chmod('data/keys/private.key', 0660);
            chmod('data/keys/public.key', 0660);
        }

        $io->writeln('Generating encryption key');
        $process = $command->runProcess($io, ['vendor/bin/generate-defuse-key']);
        $key = $process->getOutput();
        $key = str_replace("\n", "", $key);
        $filePath = $this->getSettingsFileName();
        $array = explode('/', $filePath);
        $fileName = end($array);
        $config = file_get_contents($filePath);
        $regex = '#\'encryptionKey\'\s=>\s\'[a-f|\d]+\'#';
        $replacement = "'encryptionKey' => '$key'";
        $config = \preg_replace($regex, $replacement, $config);
        file_put_contents('config/' . $fileName, $config);
    }
}
