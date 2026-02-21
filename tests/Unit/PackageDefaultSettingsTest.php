<?php

declare(strict_types=1);

namespace Tests\Unit;

use Barnacle\Container;
use Bone\OAuth2\BoneOAuth2Package;
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
use Bone\Server\SiteConfig;
use Bone\View\ViewEngineInterface;
use Codeception\Test\Unit;
use Del\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\CryptKey;

class PackageDefaultSettingsTest extends Unit
{
    private Container $container;
    private BoneOAuth2Package $package;

    protected function _before()
    {
        $this->container = new Container();
        $entityManager = $this->createMock(EntityManagerInterface::class);

        // Mock repositories
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

        $this->container[EntityManagerInterface::class] = $entityManager;
        $this->container[SiteConfig::class] = $this->createMock(SiteConfig::class);
        $this->container[ViewEngineInterface::class] = $this->createMock(ViewEngineInterface::class);

        // Create a valid private key file for CryptKey
        $keyPath = codecept_data_dir('private.key');
        if (!file_exists($keyPath)) {
             $config = [
                "digest_alg" => "sha256",
                "private_key_bits" => 2048,
                "private_key_type" => OPENSSL_KEYTYPE_RSA,
            ];
            $res = openssl_pkey_new($config);
            openssl_pkey_export($res, $privKey);
            file_put_contents($keyPath, $privKey);
        }

        $pubKeyPath = codecept_data_dir('public.key');
        if (!file_exists($pubKeyPath)) {
             $config = [
                "digest_alg" => "sha256",
                "private_key_bits" => 2048,
                "private_key_type" => OPENSSL_KEYTYPE_RSA,
            ];
            $res = openssl_pkey_new($config);
            $pubKey = openssl_pkey_get_details($res);
            file_put_contents($pubKeyPath, $pubKey['key']);
        }

        // Minimal settings to trigger defaults
        // We use a CryptKey object to bypass permission checks for BOTH keys
        $settings = [
            'clientCredentialsTokenTTL' => null,
            'authCodeTTL' => null,
            'accessTokenTTL' => null,
            'refreshTokenTTL' => null,
            'privateKeyPath' => new CryptKey($keyPath, null, false),
            'publicKeyPath' => new CryptKey($pubKeyPath, null, false),
            'encryptionKey' => 'def000002e113a725ebc60dc305541e09588776f65a17cf3258d8f7194bc3c38f62b0fe818cc026833bd1226b52e721534dee4e9db832977e1bc9ce764b848ad9fb3581f',
        ];
        $this->container['oauth2'] = $settings;

        $this->package = new BoneOAuth2Package();
    }

    public function testAddToContainerWithDefaultSettings()
    {
        $this->package->addToContainer($this->container);

        // This should trigger the factory and use default TTLs
        $server = $this->container->get(AuthorizationServer::class);
        $this->assertInstanceOf(AuthorizationServer::class, $server);

        // This should trigger the ResourceServer factory
        $resourceServer = $this->container->get(ResourceServer::class);
        $this->assertInstanceOf(ResourceServer::class, $resourceServer);
    }
}

namespace League\OAuth2\Server;

// Mock fileperms to bypass permission check in CryptKey
function fileperms($filename)
{
    return 0600;
}
