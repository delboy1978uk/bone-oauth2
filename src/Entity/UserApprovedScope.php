<?php

declare(strict_types=1);

namespace Bone\OAuth2\Entity;

use Del\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

#[ORM\Entity(repositoryClass: 'Bone\OAuth2\Repository\UserApprovedScopeRepository')]
#[ORM\Table(name: 'UserApprovedScope')]
class UserApprovedScope
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', length: 11)]
    private int $id;

    #[ORM\ManyToOne(targetEntity: 'Del\Entity\User')]
    #[ORM\JoinColumn(name: 'user', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: 'Bone\OAuth2\Entity\Client')]
    #[ORM\JoinColumn(name: 'client', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ClientEntityInterface $client;

    #[ORM\ManyToOne(targetEntity: 'Bone\OAuth2\Entity\Scope')]
    #[ORM\JoinColumn(name: 'scope', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ScopeEntityInterface $scope;

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = (int) $id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getClient(): ClientEntityInterface
    {
        return $this->client;
    }

    public function setClient(ClientEntityInterface $client): void
    {
        $this->client = $client;
    }

    public function getScope(): ScopeEntityInterface
    {
        return $this->scope;
    }

    public function setScope(ScopeEntityInterface $scope): void
    {
        $this->scope = $scope;
    }
}
