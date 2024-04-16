<?php

declare(strict_types=1);

namespace Bone\OAuth2\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

#[ORM\Entity(repositoryClass: 'Bone\OAuth2\Repository\ScopeRepository')]
#[ORM\Table(name: 'Scope')]
class Scope implements ScopeEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 40)]
    protected string $identifier;

    #[ORM\Column(type: 'string', length: 255)]
    private string $description;

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function jsonSerialize(): string
    {
        return $this->getIdentifier();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
