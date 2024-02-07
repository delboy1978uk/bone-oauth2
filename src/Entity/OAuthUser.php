<?php

declare(strict_types=1);

namespace Bone\OAuth2\Entity;

use Del\Entity\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use League\OAuth2\Server\Entities\UserEntityInterface;

/**
 * @ORM\Entity(repositoryClass="Bone\OAuth2\Repository\UserRepository")
 */
class OAuthUser extends BaseUser implements UserEntityInterface
{
    /**
     * @return self
     */
    public function getIdentifier()
    {
        return $this;
    }
}
