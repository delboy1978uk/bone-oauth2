<?php

declare(strict_types=1);

namespace Bone\OAuth2\Repository;

use Bone\OAuth2\Exception\OAuthException;
use Doctrine\ORM\EntityRepository;
use Exception;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\Scope;

class ScopeRepository extends EntityRepository implements ScopeRepositoryInterface
{
    public function getScopeEntityByIdentifier($identifier): ?Scope
    {
        /** @var Scope $scope */
        $scope = $this->findOneBy(['identifier' => $identifier]);

        return $scope;
    }

    /**
     * @return ScopeEntityInterface[]
     */
    public function finalizeScopes(array $scopes, $grantType, ClientEntityInterface $clientEntity, $userIdentifier = null): array
    {
        /** @var Client $clientEntity */
        $clientScopes = $clientEntity->getScopes()->getValues();

        $finalScopes = array_uintersect($scopes, $clientScopes, function($a, $b) {
            return strcmp(spl_object_hash($a), spl_object_hash($b));
        });

        if (count($finalScopes) < count($scopes)) {
            throw new OAuthException('Scopes not authorised.', 403);
        }

        return $finalScopes;
    }

    public function create(Scope $scope): Scope
    {
        $this->_em->persist($scope);
        $this->_em->flush($scope);

        return $scope;
    }

    public function save(Scope $scope): Scope
    {
        $this->_em->flush($scope);

        return $scope;
    }
}
