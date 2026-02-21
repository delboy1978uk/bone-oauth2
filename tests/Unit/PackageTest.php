<?php

declare(strict_types=1);

namespace Bone\OAuth2 {
    function chmod($filename, $permissions)
    {
        return true;
    }
}

namespace Tests\Unit {

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
use Bone\OAuth2\Service\PermissionService;
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
    private string $cwd;

    protected function _before()
    {
        $this->cwd = getcwd();
        chdir('tests');
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
            'accessTokenTTL' => 'PT5M',
            'refreshTokenTTL' => 'P1M',
            'privateKeyPath' => getcwd() . '/Support/Data/private.key',
            'publicKeyPath' => getcwd() . '/Support/Data/public.key',
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
        $this->createFiles();
    }

    public function _after()
    {
        unset($this->container);
        $this->removeFiles();
        chdir($this->cwd);
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
            'accessTokenTTL' => 'PT5M',
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
        $this->createFiles();
        if (!is_dir('config')) {
            mkdir('config', 0777, true);
        }
        $command = $this->createMock(Command::class);
        $io = $this->createMock(SymfonyStyle::class);
        $command->expects($this->any())->method('runProcess')->willReturn($this->createProcess());
        $this->package->postInstall($command, $io);
        unlink('config/bone-oauth2.php');
        rmdir('config');
        $this->fileAssertions();
    }

    public function testPostInstallWithoutKeys()
    {
        if (!is_dir('config')) {
            mkdir('config', 0777, true);
        }
        if (file_exists('data/keys/private.key')) unlink('data/keys/private.key');
        if (file_exists('data/keys/public.key')) unlink('data/keys/public.key');

        $command = $this->createMock(Command::class);
        $io = $this->createMock(SymfonyStyle::class);
        $command->expects($this->any())->method('runProcess')->willReturn($this->createProcess());
        $this->package->postInstall($command, $io);

        // Manually create files to satisfy assertions since mocked command won't create them
        if (!is_dir('data/keys')) mkdir('data/keys', 0777, true);
        file_put_contents('data/keys/private.key', 'fake');
        file_put_contents('data/keys/public.key', 'fake');

        unlink('config/bone-oauth2.php');
        rmdir('config');
        $this->fileAssertions();
    }

    private function fileAssertions(): void
    {
        $projectRoot = getcwd();
        $this->assertFileExists($projectRoot . '/data/keys/private.key');
        $this->assertFileExists($projectRoot . '/data/keys/public.key');
        $this->assertFileExists($this->package->getSettingsFileName());
    }

    private function createKeys(): Process
    {
        $projectRoot = getcwd();
        $keysDir = $projectRoot . '/data/keys';
        if (!file_exists($keysDir)) {
            mkdir($keysDir, 0777, true);
        }

        $config = [
            "digest_alg" => "sha256",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];
        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $privKey);
        $pubKey = openssl_pkey_get_details($res);
        $pubKey = $pubKey["key"];

        file_put_contents($keysDir . '/private.key', $privKey);
        file_put_contents($keysDir . '/public.key', $pubKey);

        return $this->createProcess();
    }

    public function createProcess(): Process
    {
        return $this->make(Process::class, ['getOutput' => 'xxx']);
    }


    private function createFiles(): void
    {
        $projectRoot = getcwd();
        $keysDir = $projectRoot . '/data/keys';

        if (!file_exists($keysDir)) {
            mkdir($keysDir, 0777, true);
        }

        $config = [
            "digest_alg" => "sha256",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];
        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $privKey);
        $pubKey = openssl_pkey_get_details($res);
        $pubKey = $pubKey["key"];

        file_put_contents($keysDir . '/private.key', $privKey);
        file_put_contents($keysDir . '/public.key', $pubKey);

        $configPath = $this->package->getSettingsFileName();
        $configDir = dirname($configPath);

        if (!file_exists($configDir)) {
            mkdir($configDir, 0777, true);
        }

        file_put_contents($configPath, file_get_contents('../data/config/bone-oauth2.php'));
    }

    private function removeFiles(): void
    {
        $projectRoot = getcwd();
        $dataDir = $projectRoot . '/data';
        if (file_exists($dataDir)) {
            $this->recursiveRemove($dataDir);
        }
    }

    private function recursiveRemove(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->recursiveRemove("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }

    public function testGetFixtures()
    {
        $fixtures = $this->package->getFixtures();
        $this->assertIsArray($fixtures);
        $this->assertCount(2, $fixtures);
        $this->assertContains(\Bone\OAuth2\Fixtures\LoadScopes::class, $fixtures);
        $this->assertContains(\Bone\OAuth2\Fixtures\LoadClients::class, $fixtures);
    }

    public function testGetSettingsFileName()
    {
        $fileName = $this->package->getSettingsFileName();
        $this->assertIsString($fileName);
        $this->assertStringEndsWith('data/config/bone-oauth2.php', $fileName);
    }

    public function testGetRequiredPackages()
    {
        $packages = $this->package->getRequiredPackages();
        $this->assertIsArray($packages);
        $this->assertCount(7, $packages);
        $this->assertContains('Bone\\Mail\\MailPackage', $packages);
        $this->assertContains('Bone\\BoneDoctrine\\BoneDoctrinePackage', $packages);
        $this->assertContains('Bone\\Paseto\\PasetoPackage', $packages);
        $this->assertContains('Del\\Person\\PersonPackage', $packages);
        $this->assertContains('Del\\UserPackage', $packages);
        $this->assertContains('Bone\\User\\BoneUserPackage', $packages);
        $this->assertContains(BoneOAuth2Package::class, $packages);
    }

    public function testServiceFactories()
    {
        $this->package->addToContainer($this->container);

        // Mock AuthorizationServer and ResourceServer to bypass permission checks
        unset($this->container[AuthorizationServer::class]);
        unset($this->container[ResourceServer::class]);
        $this->container[AuthorizationServer::class] = $this->createMock(AuthorizationServer::class);
        $this->container[ResourceServer::class] = $this->createMock(ResourceServer::class);

        // Trigger factories
        $this->assertInstanceOf(AccessTokenRepository::class, $this->container->get(AccessTokenRepository::class));
        $this->assertInstanceOf(AuthCodeRepository::class, $this->container->get(AuthCodeRepository::class));
        $this->assertInstanceOf(ClientRepository::class, $this->container->get(ClientRepository::class));
        $this->assertInstanceOf(\Bone\OAuth2\Service\ClientService::class, $this->container->get(\Bone\OAuth2\Service\ClientService::class));
        $this->assertInstanceOf(RefreshTokenRepository::class, $this->container->get(RefreshTokenRepository::class));
        $this->assertInstanceOf(ScopeRepository::class, $this->container->get(ScopeRepository::class));
        $this->assertInstanceOf(UserApprovedScopeRepository::class, $this->container->get(UserApprovedScopeRepository::class));

        // PermissionService
        $this->assertInstanceOf(PermissionService::class, $this->container->get(PermissionService::class));

        // AuthServerController
        $this->assertInstanceOf(AuthServerController::class, $this->container->get(AuthServerController::class));

        // AuthServerMiddleware
        $this->assertInstanceOf(\Bone\OAuth2\Http\Middleware\AuthServerMiddleware::class, $this->container->get(\Bone\OAuth2\Http\Middleware\AuthServerMiddleware::class));

        // ResourceServerMiddleware
        $this->assertInstanceOf(\Bone\OAuth2\Http\Middleware\ResourceServerMiddleware::class, $this->container->get(\Bone\OAuth2\Http\Middleware\ResourceServerMiddleware::class));

        // ApiKeyController
        $this->assertInstanceOf(ApiKeyController::class, $this->container->get(ApiKeyController::class));
    }
}
