<?php

declare(strict_types=1);

namespace Bone\OAuth2\Entity;

use Bone\BoneDoctrine\Traits\HasId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'ClientCallbackUrl')]
#[ORM\Entity]
class ClientCallbackUrl
{
    use HasId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $url;

    #[ORM\ManyToOne(targetEntity: 'Bone\OAuth2\Entity\Client', inversedBy: 'callbackUrls')]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Client $client;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;
    }
}
