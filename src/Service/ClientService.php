<?php

namespace Bone\OAuth2\Service;

use Bone\OAuth2\Client;
use Bone\OAuth2\Repository\ClientRepository;

/**
 * Class ClientService
 * @package Entity\OAuth\Service
 */
class ClientService
{
    /**
     * @var ClientRepository
     */
    private $clientRepository;

    /**
     * ClientService constructor.
     * @param ClientRepository $clientRepository
     */
    public function __construct(ClientRepository $clientRepository)
    {
        $this->clientRepository = $clientRepository;
    }

    /**
     * @return ClientRepository
     */
    public function getClientRepository(): ClientRepository
    {
        return $this->clientRepository;
    }

    /**
     * @param Client $client
     * @return Client
     */
    public function generateSecret(Client $client)
    {
        $time = microtime();
        $name = $client->getName();
        $secret = password_hash($name . $time  . 'bone', PASSWORD_BCRYPT);
        $base64 = base64_encode($secret);
        $client->setSecret($base64);
        return $client;
    }
}