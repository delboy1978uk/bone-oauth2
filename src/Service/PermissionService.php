<?php

namespace Bone\OAuth2\Service;

use Bone\OAuth2\Entity\OAuthUser;
use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Entity\UserApprovedScope;
use Bone\OAuth2\Repository\UserApprovedScopeRepository;
use League\OAuth2\Server\Entities\ClientEntityInterface;

/**
 * Class PermissionService
 * @package Entity\OAuth\Service
 */
class PermissionService
{
    /**
     * @var UserApprovedScopeRepository
     */
    private $approvedScopeRepository;

    /**
     * ClientService constructor.
     * @param UserApprovedScopeRepository $approvedScopeRepository
     */
    public function __construct(UserApprovedScopeRepository $approvedScopeRepository)
    {
        $this->approvedScopeRepository = $approvedScopeRepository;
    }

    /**
     * @return UserApprovedScopeRepository
     */
    public function getRepository(): UserApprovedScopeRepository
    {
        return $this->approvedScopeRepository;
    }

    /**
     * @param OAuthUser $user
     * @param ClientEntityInterface $client
     * @return array
     */
    public function getScopes(OAuthUser $user, ClientEntityInterface $client): array
    {
        return $this->approvedScopeRepository->findBy([
            'user' => $user->getId(),
            'client' => $client->getId(),
        ]);
    }

    /**
     * @param OAuthUser $user
     * @param ClientEntityInterface $client
     * @param Scope[] $scopes
     */
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