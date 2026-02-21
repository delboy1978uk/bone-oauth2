<?php

declare(strict_types=1);

namespace Bone\OAuth2\Entity;

use Del\Entity\User;
use League\OAuth2\Server\Entities\UserEntityInterface;

class OAuthUser implements UserEntityInterface
{
    private User $user;

    public function getIdentifier(): int
    {
        return $this->user->getId();
    }

    public static function createFromBaseUser(User $baseUser): self
    {
        $instance = new self();
        $instance->user = $baseUser;

        return $instance;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
