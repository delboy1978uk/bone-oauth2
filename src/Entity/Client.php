<?php

declare(strict_types=1);

namespace Bone\OAuth2\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use League\OAuth2\Server\Entities\ClientEntityInterface;

#[ORM\Table(name: 'Client')]
#[ORM\Entity(repositoryClass: 'Bone\OAuth2\Repository\ClientRepository')]
#[ORM\UniqueConstraint(name: 'identifier_idx', columns: ['identifier'])]
class Client implements ClientEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 40)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    private string $description;

    #[ORM\Column(type: 'string', length: 100)]
    private string $icon;

    #[ORM\Column(type: 'string', length: 20)]
    private string $grantType;

    #[ORM\Column(type: 'string', length: 255)]
    private string $redirectUri;

    #[ORM\Column(type: 'string', length: 40)]
    private string $identifier;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $secret = null;

    #[ORM\Column(type: 'boolean')]
    private bool $confidential = false;

    #[ORM\ManyToOne(targetEntity: 'Bone\OAuth2\Entity\OAuthUser', cascade: ["merge"])]
    private OAuthUser $user;

    #[ORM\ManyToMany(targetEntity: 'Bone\OAuth2\Entity\Scope')]
    #[ORM\JoinTable(name: 'Client_Scope',)]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'scope_id', referencedColumnName: 'id')]
    private Collection $scopes;

    public function __construct()
    {
        $this->scopes = new ArrayCollection();
    }

    public function getIdentifier():  string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier(string $identifier)
    {
        $this->identifier = $identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setRedirectUri(string $redirectUri): void
    {
        $this->redirectUri = $redirectUri;
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }

    public function isConfidential(): bool
    {
        return $this->confidential;
    }

    public function setConfidential(bool $confidential): void
    {
        $this->confidential = $confidential;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): void
    {
        $this->icon = $icon;
    }

    public function getGrantType(): string
    {
        return $this->grantType;
    }

    public function setGrantType(string $grantType): void
    {
        $this->grantType = $grantType;
    }

    public function getUser(): OAuthUser
    {
        return $this->user;
    }

    public function setUser(OAuthUser $user): void
    {
        $this->user = $user;
    }

    public function getScopes(): Collection
    {
        return $this->scopes;
    }

    public function setScopes(Collection $scopes): void
    {
        $this->scopes = $scopes;
    }
}
