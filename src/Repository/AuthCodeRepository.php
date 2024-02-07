<?php

declare(strict_types=1);

namespace Bone\OAuth2\Repository;

use Bone\OAuth2\Exception\OAuthException;
use DateTimeImmutable;
use Doctrine\ORM\EntityRepository;
use Exception;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use Bone\OAuth2\Entity\AuthCode;
use Bone\OAuth2\Entity\Client;

class AuthCodeRepository extends EntityRepository implements AuthCodeRepositoryInterface
{
    public function getNewAuthCode(): AuthCode
    {
        return new AuthCode();
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        $date = new DateTimeImmutable('+24 hours');
        $authCodeEntity->setExpiryDateTime($date);
        /** @var Client $client */
        $client = $this->_em->getRepository(Client::class)
            ->findOneBy([
                'identifier' => $authCodeEntity->getClient()->getIdentifier()
            ]);
        $authCodeEntity->setClient($client);
        $this->_em->persist($authCodeEntity);
        $this->_em->flush();
    }

    public function revokeAuthCode(string $codeId): void
    {
        /** @var AuthCode $token */
        $code = $this->findOneBy(['identifier' => $codeId]);

        if(!$code) {
            throw new OAuthException('Token not found', 404);
        }

        $code->setRevoked(true);
        $this->_em->flush($code);
    }

    public function isAuthCodeRevoked($codeId): bool
    {
        /** @var AuthCode $code */
        $code = $this->findOneBy(['identifier' => $codeId]);

        return !$code || $code->getExpiryDateTime() < new DateTimeImmutable() || $code->isRevoked();
    }
}
