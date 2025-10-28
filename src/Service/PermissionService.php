<?php

declare(strict_types=1);

namespace Bone\OAuth2\Service;

use Del\Entity\User;
use Bone\OAuth2\Entity\UserApprovedScope;
use Bone\OAuth2\Repository\UserApprovedScopeRepository;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

class PermissionService
{
    public function __construct(
        private UserApprovedScopeRepository $approvedScopeRepository
    ) {
    }

    public function getRepository(): UserApprovedScopeRepository
    {
        return $this->approvedScopeRepository;
    }

    /**
     * @return array<int, UserApprovedScope>
     */
    public function getScopes(User $user, ClientEntityInterface $client): array
    {
        return $this->approvedScopeRepository->findBy([
            'user' => $user->getId(),
            'client' => $client->getId(),
        ]);
    }

    /**
     * @param array<ScopeEntityInterface> $scopes
     */
    public function addScopes(User $user, ClientEntityInterface $client, array $scopes): void
    {
        foreach ($scopes as $scope) {
            $approvedScope = new UserApprovedScope();
            $approvedScope->setUser($user);
            $approvedScope->setClient($client);
            $approvedScope->setScope($scope);
            $this->getRepository()->save($approvedScope);
        }
    }
}
