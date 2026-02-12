<?php

declare(strict_types=1);

namespace Tests\Unit;

use Bone\OAuth2\BoneOAuth2Package;
use Bone\OAuth2\Fixtures\LoadClients;
use Bone\OAuth2\Fixtures\LoadScopes;
use Bone\Console\Command;
use Codeception\Test\Unit;
use Symfony\Component\Console\Style\SymfonyStyle;

class BoneOAuth2PackageUncoveredTest extends Unit
{
    private BoneOAuth2Package $package;

    protected function _before()
    {
        $this->package = new BoneOAuth2Package();
    }

    public function testGetFixtures()
    {
        $fixtures = $this->package->getFixtures();
        
        $this->assertIsArray($fixtures);
        $this->assertCount(2, $fixtures);
        $this->assertContains(LoadScopes::class, $fixtures);
        $this->assertContains(LoadClients::class, $fixtures);
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
}
