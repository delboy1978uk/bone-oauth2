<?php

declare(strict_types=1);

namespace Bone\OAuth2\Repository;

use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Entity\UserApprovedScope;
use Doctrine\ORM\EntityRepository;

/** @extends EntityRepository<UserApprovedScope> */
class UserApprovedScopeRepository extends EntityRepository
{
    public function save(UserApprovedScope $scope): void
    {
        $this->getEntityManager()->persist($scope);
        $this->getEntityManager()->flush();;
    }
}
