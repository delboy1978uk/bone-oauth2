<?php

declare(strict_types=1);

namespace Bone\OAuth2\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

#[ORM\Entity(repositoryClass: 'Bone\OAuth2\Repository\AuthCodeRepository')]
#[ORM\Table(name: 'AuthCode')]
class AuthCode implements AuthCodeEntityInterface
{
    protected ArrayCollection $scopes;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $redirectUri = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected DateTimeImmutable $expiryDateTime;

    #[ORM\Column(type: 'integer', length: 11)]
    protected int $userIdentifier;

    #[ORM\ManyToOne(targetEntity: 'Bone\OAuth2\Entity\Client')]
    #[ORM\JoinColumn(name: 'client', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ClientEntityInterface $client;

    #[ORM\Column(type: 'text', nullable: false)]
    protected string $identifier;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    protected int $id;

    #[ORM\Column(type: 'boolean')]
    protected bool $revoked = false;

    public function __construct()
    {
        $this->scopes = new ArrayCollection();
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier($identifier): void
    {
        $this->identifier = $identifier;
    }

    public function addScope(ScopeEntityInterface $scope): void
    {
        $this->scopes->add($scope);
    }

    /** @return ScopeEntityInterface[] */
    public function getScopes(): array
    {
        return $this->scopes->toArray();
    }

    public function getExpiryDateTime(): DateTimeImmutable
    {
        return $this->expiryDateTime;
    }

    public function setExpiryDateTime(DateTimeImmutable $dateTime): void
    {
        $this->expiryDateTime = $dateTime;
    }

    public function setUserIdentifier($identifier): void
    {
        $this->userIdentifier = $identifier;
    }

    public function getUserIdentifier(): ?string
    {
        return (string) $this->userIdentifier;
    }

    public function getClient(): ClientEntityInterface
    {
        return $this->client;
    }

    public function setClient(ClientEntityInterface $client): void
    {
        $this->client = $client;
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    public function setRedirectUri($uri): void
    {
        $this->redirectUri = $uri;
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function setRevoked(bool $revoked): void
    {
        $this->revoked = $revoked;
    }
}
