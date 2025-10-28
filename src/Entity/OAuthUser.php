<?php

declare(strict_types=1);

namespace Bone\OAuth2\Entity;

use Del\Entity\User;
use Doctrine\ORM\Mapping as ORM;
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
        $data = $baseUser->toArray();
        $instance = new self();
        $instance->user = $baseUser;

        return $instance;
    }
}
