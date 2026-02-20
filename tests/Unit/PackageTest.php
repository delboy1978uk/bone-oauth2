<?php

declare(strict_types=1);

namespace Tests\Unit;

use Barnacle\Container;
use Barnacle\Exception\ContainerException;
use Bone\Console\Command;
use Bone\Contracts\Service\TranslatorInterface;
use Bone\OAuth2\BoneOAuth2Package;
use Bone\OAuth2\Controller\ApiKeyController;
use Bone\OAuth2\Controller\AuthServerController;
use Bone\OAuth2\Entity\AccessToken;
use Bone\OAuth2\Entity\AuthCode;
use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\RefreshToken;
use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Entity\UserApprovedScope;
use Bone\OAuth2\Repository\AccessTokenRepository;
use Bone\OAuth2\Repository\AuthCodeRepository;
use Bone\OAuth2\Repository\ClientRepository;
use Bone\OAuth2\Repository\RefreshTokenRepository;
use Bone\OAuth2\Repository\ScopeRepository;
use Bone\OAuth2\Repository\UserApprovedScopeRepository;
use Bone\OAuth2\Repository\UserRepository;
use Bone\Router\Router;
use Bone\Server\SiteConfig;
use Bone\User\Http\Middleware\SessionAuth;
use Bone\User\Http\Middleware\SessionAuthRedirect;
use Bone\View\ViewEngineInterface;
use Codeception\Test\Unit;
use Del\Entity\User;
use Del\Service\UserService;
use Del\SessionManager;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\ResourceServer;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Tests\Support\UnitTester;

class PackageTest extends Unit
{
    protected UnitTester $tester;
    private Container $container;
    private BoneOAuth2Package $package;

    protected function _before()
    {
        $this->container = new Container();
        $userService = $this->createMock(UserService::class);
        $view = $this->createMock(ViewEngineInterface::class);
        $authMiddleware = $this->createMock(SessionAuth::class);
        $redirectMiddleware = $this->createMock(SessionAuthRedirect::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $sessionManager = SessionManager::getInstance();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $accessTokenRepository = $this->createMock(AccessTokenRepository::class);
        $clientRepository = $this->createMock(ClientRepository::class);
        $scopeRepository = $this->createMock(ScopeRepository::class);
        $authCodeRepository = $this->createMock(AuthCodeRepository::class);
        $refreshTokenRepository = $this->createMock(RefreshTokenRepository::class);
        $userScopeRepository = $this->createMock(UserApprovedScopeRepository::class);
        $userRepository = $this->createMock(UserRepository::class);
        $map = [
            [AccessToken::class, $accessTokenRepository],
            [Client::class, $clientRepository],
            [Scope::class, $scopeRepository],
            [AuthCode::class, $authCodeRepository],
            [RefreshToken::class, $refreshTokenRepository],
            [UserApprovedScope::class, $userScopeRepository],
            [User::class, $userRepository],
        ];
        $entityManager->method('getRepository')->with()->willReturnMap($map);
        $settings = [
            'clientCredentialsTokenTTL' => 'PT1H',
            'authCodeTTL' => 'PT1M',
            'accessTokenTTL' => 'P1M',
            'refreshTokenTTL' => 'P1M',
            'privateKeyPath' => __DIR__ . '/../Support/Data/private.key',
            'publicKeyPath' => __DIR__ . '/../Support/Data/public.key',
            'encryptionKey' => 'def000002e113a725ebc60dc305541e09588776f65a17cf3258d8f7194bc3c38f62b0fe818cc026833bd1226b52e721534dee4e9db832977e1bc9ce764b848ad9fb3581f',
        ];
        $siteConfig = $this->createMock(SiteConfig::class);
        $this->container['oauth2'] = $settings;
        $this->container[SiteConfig::class] = $siteConfig;
        $this->container[UserRepository::class] = $userRepository;
        $this->container[UserService::class] = $userService;
        $this->container[ViewEngineInterface::class] = $view;
        $this->container[SessionAuth::class] = $authMiddleware;
        $this->container[SessionAuthRedirect::class] = $redirectMiddleware;
        $this->container[SessionManager::class] = $sessionManager;
        $this->container[EntityManagerInterface::class] = $entityManager;
        $this->container[TranslatorInterface::class] = $translator;
        $this->package = new BoneOAuth2Package();
    }

    public function _after()
    {
        unset($this->container);
    }

    public function testAddToContainer()
    {
                $this->package->addToContainer($this->container);
        $this->container[AuthorizationServer::class] = $this->createMock(AuthorizationServer::class);
        $this->container[ResourceServer::class] = $this->createMock(ResourceServer::class);
        self::assertTrue($this->container->has(AuthorizationServer::class));
    }

    public function testAddRouting()
    {
                $this->package->addToContainer($this->container);
        $this->container[AuthorizationServer::class] = $this->createMock(AuthorizationServer::class);
        $this->container[ResourceServer::class] = $this->createMock(ResourceServer::class);
        $router = $this->createMock(Router::class);
        $router->expects(self::atLeast(11))->method('map');
        $this->package->addRoutes($this->container, $router);
    }

    public function testAddCommands()
    {
                $this->package->addToContainer($this->container);
        $this->container[AuthorizationServer::class] = $this->createMock(AuthorizationServer::class);
        $this->container[ResourceServer::class] = $this->createMock(ResourceServer::class);
        $result = $this->package->registerConsoleCommands($this->container);
        self::assertIsArray($result);
    }

    public function testEntityPath()
    {
                $this->package->addToContainer($this->container);
        $this->container[AuthorizationServer::class] = $this->createMock(AuthorizationServer::class);
        $this->container[ResourceServer::class] = $this->createMock(ResourceServer::class);
        self::assertStringContainsString('bone-oauth2/src/Entity', $this->package->getEntityPath());
    }

    public function testMissingKeys()
    {
        $settings = [
            'clientCredentialsTokenTTL' => 'PT1H',
            'authCodeTTL' => 'PT1M',
            'accessTokenTTL' => 'P1M',
            'refreshTokenTTL' => 'P1M',
            'privateKeyPath' => __DIR__ . '/fail/private.key',
            'publicKeyPath' => __DIR__ . '/fail/public.key',
            'encryptionKey' => 'def000002e113a725ebc60dc305541e09588776f65a17cf3258d8f7194bc3c38f62b0fe818cc026833bd1226b52e721534dee4e9db832977e1bc9ce764b848ad9fb3581f',
        ];
        $this->container['oauth2'] = $settings;
                $this->package->addToContainer($this->container);
        $this->expectException(ContainerException::class);
        $this->container->get(ResourceServer::class);
    }

    public function testContainerContainsservices()
    {
                $this->package->addToContainer($this->container);
        $this->container[AuthorizationServer::class] = $this->createMock(AuthorizationServer::class);
        $this->container[ResourceServer::class] = $this->createMock(ResourceServer::class);
        self::assertTrue($this->container->has(ApiKeyController::class));
        self::assertInstanceOf(ApiKeyController::class, $this->container->get(ApiKeyController::class));
        self::assertTrue($this->container->has(AuthServerController::class));
        self::assertInstanceOf(AuthServerController::class, $this->container->get(AuthServerController::class));
        self::assertTrue($this->container->has(UserRepository::class));
        self::assertInstanceOf(UserRepository::class, $this->container->get(UserRepository::class));
    }

    public function testPostInstallWithKeys()
    {
        $cwd = getcwd();
        chdir('tests');
        $command = $this->createMock(Command::class);
        $this->createKeys();
        $io = $this->createMock(SymfonyStyle::class);
        $command->expects($this->once())->method('runProcess');
        $this->package->postInstall($command, $io);
        $this->fileAssertions();
        $this->removeFiles();
        chdir($cwd);
    }

    public function testPostInstallWithoutKeys()
    {
        $cwd = getcwd();
        chdir('tests');
        $command = $this->createMock(Command::class);
        $io = $this->createMock(SymfonyStyle::class);
        $command->expects($this->any())->method('runProcess')->willReturnMap([
            [$io, ['openssl', 'genrsa', '-out', 'private.key', '2048'], $this->createKeys()],
            [$io, ['vendor/bin/generate-defuse-key'], $this->createProcess()]
        ]);


        $this->package->postInstall($command, $io);
        $this->fileAssertions();
        $this->removeFiles();
        chdir($cwd);
    }

    private function fileAssertions(): void
    {
        $this->assertFileExists('data/keys/private.key');
        $this->assertFileExists('data/keys/public.key');
        $this->assertFileExists('config/bone-oauth2.php');
    }


    private function createKeys(): Process
    {
        file_put_contents('data/keys/private.key', 'fake');
        file_put_contents('data/keys/public.key', 'fake');

        return $this->createProcess();
    }

    public function createProcess(): Process
    {
        return $this->make(Process::class, ['getOutput' => 'xxx']);
    }

    private function removeFiles(): void
    {
        unlink('data/keys/private.key');
        unlink('data/keys/public.key');
        unlink('config/bone-oauth2.php');
    }
}
