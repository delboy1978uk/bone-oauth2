<?php

namespace Bone\OAuth2\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\UnitOfWork;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use Bone\OAuth2\Entity\AccessToken;
use Bone\OAuth2\Entity\RefreshToken;

/**
 * Class RefreshTokenRepository
 * @package OAuth\Repository
 */
class RefreshTokenRepository extends EntityRepository implements RefreshTokenRepositoryInterface
{
    /**
     * @return RefreshToken
     */
    public function getNewRefreshToken()
    {
        return new RefreshToken();
    }

    /**
     * @param RefreshTokenEntityInterface $refreshTokenEntity
     * @return RefreshTokenEntityInterface
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity)
    {
        $accessToken = $refreshTokenEntity->getAccessToken();

        if ($this->_em->getUnitOfWork()->getEntityState($accessToken) !== UnitOfWork::STATE_MANAGED) {
            /** @var AccessToken $accessToken */
            $accessToken = $this->_em->merge($accessToken);
            $refreshTokenEntity->setAccessToken($accessToken);
        }

        $this->_em->persist($refreshTokenEntity);
        $this->_em->flush();

        return $refreshTokenEntity;
    }

    /**
     * @param string $tokenId
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function revokeRefreshToken($tokenId)
    {
        $token = $this->findOneBy(['identifier' => $tokenId]);

        if ($token instanceof RefreshTokenEntityInterface) {
            $this->_em->remove($token);
            $this->_em->flush();

            return true;
        }

        return false;
    }

    /**
     * @param string $tokenId
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function isRefreshTokenRevoked($tokenId)
    {
        $token = $this->findOneBy(['identifier' => $tokenId]);

        if (!$token) {
            return true;
        }

        return $token->isRevoked();
    }
}