<?php

namespace Bone\OAuth2\Entity;

use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

/**
 * @ORM\Entity(repositoryClass="Bone\OAuth2\Repository\AuthCodeRepository")
 * @ORM\Table(name="AuthCode")
 */
class AuthCode implements AuthCodeEntityInterface
{

    /**
     * @var null|string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $redirectUri;

    /**
     * @var ArrayCollection $scopes
     */
    protected $scopes;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime",nullable=true)
     */
    protected $expiryDateTime;

    /**
     * @var OAuthUser
     * @ORM\ManyToOne(targetEntity="Bone\OAuth2\Entity\OAuthUser")
     * @ORM\JoinColumn(name="user", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $userIdentifier;

    /**
     * @var ClientEntityInterface
     * @ORM\ManyToOne(targetEntity="Bone\OAuth2\Entity\Client")
     * @ORM\JoinColumn(name="client", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $client;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=false)
     */
    protected $identifier;

    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $revoked = false;

    public function __construct()
    {
        $this->scopes = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @param ScopeEntityInterface $scope
     * @return $this
     */
    public function addScope(ScopeEntityInterface $scope)
    {
        $this->scopes->add($scope);
        return $this;
    }

    /**
     * Return an array of scopes associated with the token.
     *
     * @return ScopeEntityInterface[]
     */
    public function getScopes()
    {
        return $this->scopes->toArray();
    }

    /**
     * Get the token's expiry date time.
     *
     * @return DateTime
     */
    public function getExpiryDateTime()
    {
        return $this->expiryDateTime;
    }

    /**
     * Set the date time when the token expires.
     *
     * @param DateTimeImmutable $dateTime
     */
    public function setExpiryDateTime(DateTimeImmutable $dateTime)
    {
        $this->expiryDateTime = $dateTime;
    }

    /**
     * Set the identifier of the user associated with the token.
     *
     * @param OAuthUser $identifier The identifier of the user
     */
    public function setUserIdentifier($identifier)
    {
        $this->userIdentifier = $identifier;
    }

    /**
     * Get the token user's identifier.
     *
     * @return int
     */
    public function getUserIdentifier(): int
    {
        return $this->userIdentifier->getId();
    }

    /**
     * Set the identifier of the user associated with the token.
     *
     * @param OAuthUser $identifier The identifier of the user
     */
    public function setUser(OAuthUser $user): void
    {
        $this->userIdentifier = $user;
    }

    /**
     * Get the token user's identifier.
     *
     * @return OAuthUser
     */
    public function getUser()
    {
        return $this->userIdentifier;
    }

    /**
     * Get the client that the token was issued to.
     *
     * @return ClientEntityInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set the client that the token was issued to.
     *
     * @param ClientEntityInterface $client
     */
    public function setClient(ClientEntityInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @return string
     */
    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    /**
     * @param string $uri
     */
    public function setRedirectUri($uri)
    {
        $this->redirectUri = $uri;
    }

    /**
     * @return bool
     */
    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    /**
     * @param bool $revoked
     */
    public function setRevoked(bool $revoked): void
    {
        $this->revoked = $revoked;
    }
}