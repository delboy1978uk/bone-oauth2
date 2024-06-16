<?php

declare(strict_types=1);

namespace Bone\OAuth2\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\RefreshTokenTrait;

#[ORM\Entity(repositoryClass: 'Bone\OAuth2\Repository\RefreshTokenRepository')]
#[ORM\Table(name: 'RefreshToken')]
class RefreshToken implements RefreshTokenEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id  = null;

    #[ORM\Column(type: 'string', length: 255)]
    protected string $identifier;

    #[ORM\ManyToOne(targetEntity: 'Bone\OAuth2\Entity\AccessToken')]
    protected AccessTokenEntityInterface $accessToken;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected DateTimeImmutable $expiryDateTime;

    #[ORM\Column(type: 'boolean')]
    protected bool $revoked = false;

    public function setAccessToken(AccessTokenEntityInterface $accessToken):  void
    {
        $this->accessToken = $accessToken;
    }

    public function getAccessToken(): AccessTokenEntityInterface
    {
        return $this->accessToken;
    }

    public function getExpiryDateTime(): DateTimeImmutable
    {
        return $this->expiryDateTime;
    }

    public function setExpiryDateTime(DateTimeImmutable $dateTime): void
    {
        $this->expiryDateTime = $dateTime;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier($identifier): void
    {
        $this->identifier = $identifier;
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function setRevoked(bool $revoked): void
    {
        $this->revoked = $revoked;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
