<?php

declare(strict_types=1);

namespace Bone\OAuth2\Repository;

use Del\Repository\UserRepository as UserRepo;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use Bone\OAuth2\Entity\Client;

class UserRepository extends UserRepo implements UserRepositoryInterface
{
    public function getUserEntityByUserCredentials(
        $email,
        $password,
        $grantType,
        ClientEntityInterface $client
    ): ?ClientEntityInterface
    {
        $user = $this->findOneBy(['email' => $email]);

        if ($user) {
            return $user;
            /** @todo check password client and granttype */
        }

        return false;
    }

    public function checkUserCredentials(string $email, string $password): mixed
    {
        $user = $this->findOneBy(['email' => $email]);

        if ($user) {
            return $user->verifyPassword($password);
        }

        return false;
    }

    public function getUserDetails($email): ?array
    {
        $user = $this->findOneBy(['email' => $email]);

        if ($user) {
            $user = $user->toArray();
        }

        return $user;
    }
}
