<?php

declare(strict_types=1);

namespace Bone\OAuth2\Service;

use Bone\OAuth2\Entity\OAuthUser;
use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Entity\UserApprovedScope;
use Bone\OAuth2\Repository\UserApprovedScopeRepository;
use League\OAuth2\Server\Entities\ClientEntityInterface;

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

    public function getScopes(OAuthUser $user, ClientEntityInterface $client): array
    {
        return $this->approvedScopeRepository->findBy([
            'user' => $user->getId(),
            'client' => $client->getId(),
        ]);
    }

    public function addScopes(OAuthUser $user, ClientEntityInterface $client, array $scopes): void
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
