<?php

declare(strict_types=1);

namespace Bone\OAuth2\Repository;

use Del\Entity\User;
use Del\Repository\UserRepository as UserRepo;
use Laminas\Crypt\Password\Bcrypt;
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
    ): ?UserEntityInterface
    {
        $user = $this->findOneBy(['email' => $email]);

        if ($user) {
            $bcrypt = new Bcrypt();
            $bcrypt->setCost(14);

            if(!$bcrypt->verify($password, $user->getPassword())) {
                return null;
            }

            return $user;
        }

        return null;
    }

    public function checkUserCredentials(string $email, string $password): mixed
    {
        $user = $this->findOneBy(['email' => $email]);

        if ($user) {
            return $user->verifyPassword($password);
        }

        return false;
    }

    /**
     * @return array<string, string>|null
     */
    public function getUserDetails(string $email): ?array
    {
        $user = $this->findOneBy(['email' => $email]);

        if ($user) {
            $user = $user->toArray();
        }

        return null;
    }
}
