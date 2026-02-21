<?php

declare(strict_types=1);

namespace Bone\OAuth2\Repository;

use Bone\OAuth2\Entity\OAuthUser;
use Del\Entity\User;
use Del\Repository\UserRepository as UserRepo;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;

use function password_verify;

class UserRepository extends UserRepo implements UserRepositoryInterface
{
    public function getUserEntityByUserCredentials(
        $email,
        $password,
        $grantType,
        ClientEntityInterface $clientEntity
    ): ?UserEntityInterface
    {
        $user = $this->findOneBy(['email' => $email]);

        if ($user) {
            if(!password_verify($password, $user->getPassword())) {
                return null;
            }

            return OAuthUser::createFromBaseUser($user);
        }

        return null;
    }

    public function checkUserCredentials(string $email, string $password): mixed
    {
        /** @var User $user */
        $user = $this->findOneBy(['email' => $email]);

        if ($user) {
            return password_verify($password, $user->getPassword());
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
            return $user->toArray();
        }

        return null;
    }
}
