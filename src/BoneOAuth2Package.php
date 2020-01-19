<?php

declare(strict_types=1);

namespace Bone\OAuth2;

use Barnacle\Container;
use Barnacle\RegistrationInterface;

class BoneOAuth2Package implements RegistrationInterface
{
    /**
     * @param Container $c
     */
    public function addToContainer(Container $c)
    {
        
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
