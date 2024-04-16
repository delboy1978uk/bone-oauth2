<?php

declare(strict_types=1);

namespace Bone\OAuth2\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\UnitOfWork;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use Bone\OAuth2\Entity\AccessToken;
use Bone\OAuth2\Entity\RefreshToken;

class RefreshTokenRepository extends EntityRepository implements RefreshTokenRepositoryInterface
{
    public function getNewRefreshToken(): RefreshToken
    {
        return new RefreshToken();
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): RefreshTokenEntityInterface
    {
        $accessToken = $refreshTokenEntity->getAccessToken();

        if ($this->getEntityManager()->getUnitOfWork()->getEntityState($accessToken) !== UnitOfWork::STATE_MANAGED) {
            /** @var AccessToken $accessToken */
            $accessToken = $this->getEntityManager()->merge($accessToken);
            $refreshTokenEntity->setAccessToken($accessToken);
        }

        $this->getEntityManager()->persist($refreshTokenEntity);
        $this->getEntityManager()->flush();

        return $refreshTokenEntity;
    }

    public function revokeRefreshToken(string $tokenId): bool
    {
        $token = $this->findOneBy(['identifier' => $tokenId]);

        if ($token instanceof RefreshTokenEntityInterface) {
            $this->getEntityManager()->remove($token);
            $this->getEntityManager()->flush();

            return true;
        }

        return false;
    }

    public function isRefreshTokenRevoked(string $tokenId): bool
    {
        $token = $this->findOneBy(['identifier' => $tokenId]);

        if (!$token) {
            return true;
        }

        return $token->isRevoked();
    }
}
