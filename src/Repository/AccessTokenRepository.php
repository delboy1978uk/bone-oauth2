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

class AccessTokenRepository extends EntityRepository implements AccessTokenRepositoryInterface
{
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): AccessTokenEntityInterface
    {
        $this->_em->persist($accessTokenEntity);
        $this->_em->flush();

        return $accessTokenEntity;
    }

    public function revokeAccessToken(string $tokenId): void
    {
        /** @var AccessToken $token */
        $token = $this->findOneBy(['identifier' => $tokenId]);

        if(!$token) {
            throw new OAuthException('Token not found', 404);
        }

        $token->setRevoked(true);
        $this->_em->flush($token);
    }

    public function isAccessTokenRevoked(string $tokenId): bool
    {
        /** @var null|AccessToken $token */
        $token = $this->findOneBy(['identifier' => $tokenId]);

        return !$token || $token->isRevoked();
    }

    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null): AccessToken
    {
        $accessToken = new AccessToken();
        $accessToken->setClient($clientEntity);
        $accessToken->setScopes(new ArrayCollection($scopes));
        $accessToken->setUserIdentifier($userIdentifier);

        return $accessToken;
    }
}
