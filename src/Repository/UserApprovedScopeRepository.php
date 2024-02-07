<?php

declare(strict_types=1);

namespace Bone\OAuth2\Repository;

use Bone\OAuth2\Entity\UserApprovedScope;
use Doctrine\ORM\EntityRepository;

class UserApprovedScopeRepository extends EntityRepository
{
    public function save(UserApprovedScope $scope): void
    {
        $this->_em->persist($scope);
        $this->_em->flush();;
    }
}
