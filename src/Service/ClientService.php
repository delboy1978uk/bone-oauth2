<?php

namespace Bone\OAuth2\Service;

use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\OAuthUser;
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

    /**
     * @param Client $client
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteClient(Client $client)
    {
        $this->getClientRepository()->delete($client);
    }


    /**
     * @param array $data
     * @param OAuthUser $user
     * @return Client
     */
    public function createFromArray(array $data, OAuthUser $user): Client
    {
        $client = new Client();
        $client->setName($data['name']);
        $client->setDescription($data['description']);
        $client->setIcon($data['icon']);
        $client->setIdentifier(md5($data['name']));
        $client->setRedirectUri($data['redirectUri']);
        $client->setGrantType($data['grantType']);
        $client->setUser($user);
        $this->generateSecret($client);

        if ($data['confidential'] === true || $data['confidential'] === 'confidential') {
            $client->setConfidential(true);
        }

        return $client;
    }
}