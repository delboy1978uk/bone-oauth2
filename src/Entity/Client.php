<?php

declare(strict_types=1);

namespace Bone\OAuth2\Entity;

use Bone\BoneDoctrine\Attributes\Visibility;
use Bone\BoneDoctrine\Traits\HasId;
use Bone\BoneDoctrine\Traits\HasName;
use Del\Entity\User;
use Del\Form\Field\Attributes\Field;
use Del\Form\Traits\HasFormFields;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use League\OAuth2\Server\Entities\ClientEntityInterface;

#[ORM\Table(name: 'Client')]
#[ORM\Entity(repositoryClass: 'Bone\OAuth2\Repository\ClientRepository')]
#[ORM\UniqueConstraint(name: 'identifier_idx', columns: ['identifier'])]
class Client implements ClientEntityInterface
{
    use HasId;
    use HasName;

    #[ORM\Column(type: 'string', length: 255)]
    private string $description;

    #[ORM\Column(type: 'string', length: 255)]
    private string $icon;

    #[ORM\Column(type: 'string', length: 20)]
    private string $grantType;

    /**
     * @deprecated Use callbackUrls collection instead
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $redirectUri = null;

    #[ORM\Column(type: 'string', length: 40)]
    private string $identifier;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $secret = null;

    #[ORM\Column(type: 'boolean')]
    private bool $confidential = false;

    #[ORM\Column(type: 'boolean')]
    private bool $proprietary = false;

    #[ORM\ManyToOne(targetEntity: 'Del\Entity\User')]
    private User $user;

    #[ORM\ManyToMany(targetEntity: 'Bone\OAuth2\Entity\Scope')]
    #[ORM\JoinTable(name: 'Client_Scope',)]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'scope_id', referencedColumnName: 'id')]
    private Collection $scopes;

    #[ORM\OneToMany(targetEntity: 'Bone\OAuth2\Entity\ClientCallbackUrl', mappedBy: 'client', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $callbackUrls;

    public function __construct()
    {
        $this->scopes = new ArrayCollection();
        $this->callbackUrls = new ArrayCollection();
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

    /**
     * @deprecated Use getCallbackUrls() instead
     */
    public function getRedirectUri(): ?string
    {
        return $this->redirectUri;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @deprecated Use addCallbackUrl() instead
     */
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

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
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

    public function isProprietary(): bool
    {
        return $this->proprietary;
    }

    public function setProprietary(bool $proprietary): void
    {
        $this->proprietary = $proprietary;
    }

    public function getCallbackUrls(): Collection
    {
        return $this->callbackUrls;
    }

    public function setCallbackUrls(Collection $callbackUrls): void
    {
        $this->callbackUrls = $callbackUrls;
    }

    public function addCallbackUrl(ClientCallbackUrl $callbackUrl): void
    {
        if (!$this->callbackUrls->contains($callbackUrl)) {
            $this->callbackUrls->add($callbackUrl);
            $callbackUrl->setClient($this);
        }
    }

    public function removeCallbackUrl(ClientCallbackUrl $callbackUrl): void
    {
        if ($this->callbackUrls->contains($callbackUrl)) {
            $this->callbackUrls->removeElement($callbackUrl);
        }
    }

    /**
     * Get all callback URLs as an array of strings
     * 
     * @return string[]
     */
    public function getCallbackUrlStrings(): array
    {
        return $this->callbackUrls->map(fn(ClientCallbackUrl $url) => $url->getUrl())->toArray();
    }

    /**
     * Check if a specific URL is registered as a callback URL
     */
    public function hasCallbackUrl(string $url): bool
    {
        foreach ($this->callbackUrls as $callbackUrl) {
            if ($callbackUrl->getUrl() === $url) {
                return true;
            }
        }
        return false;
    }
}
