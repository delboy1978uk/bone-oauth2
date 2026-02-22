<?php

declare(strict_types=1);

namespace Bone\OAuth2\Entity;

use Del\Entity\UserInterface;

use League\OAuth2\Server\Entities\UserEntityInterface;

class OAuthUser implements UserEntityInterface
{
    private UserInterface $user;

    public function getIdentifier(): string
    {
        return (string) $this->user->getId();
    }

    public static function createFromBaseUser(UserInterface $baseUser): self
    {
        $instance = new self();
        $instance->user = $baseUser;

        return $instance;
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }
}
