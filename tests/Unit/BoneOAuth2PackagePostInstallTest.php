<?php

namespace Bone\OAuth2\Test\Unit;

use Bone\OAuth2\BoneOAuth2Package;
use Codeception\Test\Unit;
use Bone\Console\Shell;
use League\Container\Container;

class BoneOAuth2PackagePostInstallTest extends Unit
{
    /** @var BoneOAuth2Package */
    private $package;

    /** @var Container */
    private $container;

    protected function _before()
    {
        $this->package = new BoneOAuth2Package();
        $this->container = new Container();
    }

    public function testPostInstallWithShell()
    {
        // Create a mock Shell
        $shell = $this->createMock(Shell::class);
        
        // Expect the shell to execute the key generation command
        $shell->expects($this->once())
            ->method('execute')
            ->with($this->stringContains('oauth:keys'));
        
        $this->container->add(Shell::class, $shell);
        
        // Call postInstall
        $this->package->postInstall($this->container);
    }

    public function testPostInstallWithoutShell()
    {
        // Don't add Shell to container
        // This should not throw an exception
        $this->package->postInstall($this->container);
        
        // If we get here without exception, test passes
        $this->assertTrue(true);
    }
}
