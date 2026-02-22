<?php

declare(strict_types=1);

namespace Bone\OAuth2\Entity;

use Bone\BoneDoctrine\Traits\HasId;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;

#[ORM\Entity(repositoryClass: 'Bone\OAuth2\Repository\AccessTokenRepository')]
#[ORM\Table(name: 'AccessToken')]
class AccessToken implements AccessTokenEntityInterface
{
    use HasId;
    use AccessTokenTrait;

    #[ORM\ManyToMany(targetEntity: 'Bone\OAuth2\Entity\Scope', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'AccessToken_Scope')]
    protected Collection $scopes;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected DateTimeImmutable $expiryDateTime;

    #[ORM\Column(type: 'integer', length: 11, nullable: true)]
    protected ?int $userIdentifier = null;

    #[ORM\ManyToOne(targetEntity: 'Bone\OAuth2\Entity\Client')]
    #[ORM\JoinColumn(name: 'client', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ClientEntityInterface $client;

    #[ORM\Column(type: 'text')]
    protected string $identifier = '';

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

    /** @return ScopeEntityInterface[]  */
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

    public function setUserIdentifier(string $identifier): void
    {
        $this->userIdentifier = (int) $identifier;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier ? (string) $this->userIdentifier : null;
    }

    public function getClient(): ClientEntityInterface
    {
        return $this->client;
    }

    public function setClient(ClientEntityInterface $client): void
    {
        $this->client = $client;
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

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @param Collection<string> $scopes
     */
    public function setScopes(Collection $scopes): void
    {
        $this->scopes = $scopes;
    }
}
