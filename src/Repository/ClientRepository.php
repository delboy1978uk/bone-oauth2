<?php

namespace Bone\OAuth2\Repository;

use Doctrine\ORM\UnitOfWork;
use Bone\OAuth2\Entity\Client;
use Doctrine\ORM\EntityRepository;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Bone\OAuth2\Entity\OAuthUser;

class ClientRepository extends EntityRepository implements ClientRepositoryInterface
{
    /**
     * @param string $clientIdentifier
     * @param null|string $grantType
     * @param null|string|null $clientSecret
     * @param bool $mustValidateSecret
     *
     * @return ClientEntityInterface|null
     */
    public function getClientEntity($clientIdentifier, $grantType = null, $clientSecret = null, $mustValidateSecret = true)
    {
        /** @var Client $client */
        $client = $this->findOneBy([
            'identifier' => $clientIdentifier
        ]);

        if ($client instanceof Client == false) {
            return null;
        }

        if ($mustValidateSecret === true && $client->isConfidential() === true && $clientSecret != $client->getSecret()) {
            return null;
        }

        return $client;
    }

    /**
     * @param Client $client
     * @return Client
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function create(Client $client)
    {
        $em = $this->getEntityManager();
        $user = $client->getUser();

        if ($em->getUnitOfWork()->getEntityState($user) !== UnitOfWork::STATE_MANAGED) {
            /** @var OAuthUser $user */
            $user = $em->merge($user);
            $client->setUser($user);
        }

        $em->persist($client);
        $em->flush();

        return $client;
    }

    /**
     * @param Client $client
     * @return Client
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Client $client)
    {
        $this->_em->flush($client);

        return $client;
    }

    /**
     * @param Client $client
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function delete(Client $client)
    {
        $this->_em->remove($client);
        $this->_em->flush($client);
    }

    /**
     * @param string $clientIdentifier
     * @param string|null $clientSecret
     * @param string|null $grantType
     * @return bool|void
     */
    public function validateClient($clientIdentifier, $clientSecret, $grantType)
    {
        // TODO: Implement validateClient() method.
    }


}