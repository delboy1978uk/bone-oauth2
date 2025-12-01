<?php

declare(strict_types=1);

namespace Bone\OAuth2\Repository;

use Bone\OAuth2\Exception\OAuthException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Exception;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Bone\OAuth2\Entity\AccessToken;

/** @extends EntityRepository<AccessToken> */
class AccessTokenRepository extends EntityRepository implements AccessTokenRepositoryInterface
{
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        $this->getEntityManager()->persist($accessTokenEntity);
        $this->getEntityManager()->flush();
    }

    public function revokeAccessToken($tokenId): void
    {
        /** @var ?AccessToken $token */
        $token = $this->findOneBy(['identifier' => $tokenId]);

        if(!$token) {
            throw new OAuthException('Token not found', 404);
        }

        $token->setRevoked(true);
        $this->getEntityManager()->flush();
    }

    public function isAccessTokenRevoked($tokenId): bool
    {
        /** @var null|AccessToken $token */
        $token = $this->findOneBy(['identifier' => $tokenId]);

        return !$token || $token->isRevoked();
    }

    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null): AccessToken
    {
        if (!$userIdentifier) {
            $userIdentifier = (string) $clientEntity->getUser()->getId();
        }

        $accessToken = new AccessToken();
        $accessToken->setClient($clientEntity);
        $accessToken->setScopes(new ArrayCollection($scopes));
        $accessToken->setUserIdentifier($userIdentifier);

        return $accessToken;
    }
}
