<?php

declare(strict_types=1);

namespace Bone\OAuth2\Entity;

use DateTimeInterface;
use DateTimeInterfaceImmutable;
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

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected DateTimeInterface $expiryDateTimeInterface;

    #[ORM\ManyToOne(targetEntity: 'Bone\OAuth2\Entity\OAuthUser')]
    #[ORM\JoinColumn(name: 'user', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected OAuthUser $userIdentifier;

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

    public function setIdentifier(string $identifier): void
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

    public function getExpiryDateTimeInterface(): DateTimeInterface
    {
        return $this->expiryDateTimeInterface;
    }

    public function setExpiryDateTimeInterface(DateTimeInterfaceImmutable $dateTime): void
    {
        $this->expiryDateTimeInterface = $dateTime;
    }

    public function setUserIdentifier(OAuthUser $identifier): void
    {
        $this->userIdentifier = $identifier;
    }

    public function getUserIdentifier(): int
    {
        return $this->userIdentifier->getId();
    }

    public function setUser(OAuthUser $user): void
    {
        $this->userIdentifier = $user;
    }

    public function getUser(): OAuthUser
    {
        return $this->userIdentifier;
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

    public function setRedirectUri(string $uri): void
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
