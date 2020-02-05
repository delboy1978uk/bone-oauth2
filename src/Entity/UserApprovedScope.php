<?php

namespace Bone\OAuth2\Entity;

use Doctrine\ORM\Mapping as ORM;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

/**
 * @ORM\Entity(repositoryClass="Bone\OAuth2\Repository\UserApprovedScopeRepository")
 * @ORM\Table(name="UserApprovedScope")
 */
class UserApprovedScope
{
    /**
     * @ORM\Id
     * @var string $id
     * @ORM\Column(type="integer", length=11)
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var OAuthUser $user
     * @ORM\ManyToOne(targetEntity="Bone\OAuth2\Entity\OAuthUser")
     * @ORM\JoinColumn(name="user", referencedColumnName="id", onDelete="CASCADE")
     */
    private $user;

    /**
     * @var ClientEntityInterface $client
     * @ORM\ManyToOne(targetEntity="Bone\OAuth2\Entity\Client")
     * @ORM\JoinColumn(name="client", referencedColumnName="id", onDelete="CASCADE")
     */
    private $client;

    /**
     * @var ScopeEntityInterface $scope
     * @ORM\ManyToOne(targetEntity="Bone\OAuth2\Entity\Scope")
     * @ORM\JoinColumn(name="scope", referencedColumnName="id", onDelete="CASCADE")
     */
    private $scope;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return OAuthUser
     */
    public function getUser(): OAuthUser
    {
        return $this->user;
    }

    /**
     * @param OAuthUser $user
     */
    public function setUser(OAuthUser $user): void
    {
        $this->user = $user;
    }

    /**
     * @return ClientEntityInterface
     */
    public function getClient(): ClientEntityInterface
    {
        return $this->client;
    }

    /**
     * @param ClientEntityInterface $client
     */
    public function setClient(ClientEntityInterface $client): void
    {
        $this->client = $client;
    }

    /**
     * @return ScopeEntityInterface
     */
    public function getScope(): ScopeEntityInterface
    {
        return $this->scope;
    }

    /**
     * @param ScopeEntityInterface $scope
     */
    public function setScope(ScopeEntityInterface $scope): void
    {
        $this->scope = $scope;
    }
}