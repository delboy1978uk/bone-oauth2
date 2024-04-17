<?php

namespace Bone\OAuth2\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Bone\OAuth2\Entity\Client;
use Doctrine\ORM\EntityRepository;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Bone\OAuth2\Entity\OAuthUser;

/** @extends EntityRepository<Client> */
class ClientRepository extends EntityRepository implements ClientRepositoryInterface
{
    public function getClientEntity($clientIdentifier): ?Client
    {
        $client = $this->findOneBy([
            'identifier' => $clientIdentifier
        ]);

        if ($client instanceof Client === false) {
            return null;
        }

        return $client;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return parent::getEntityManager();
    }


    public function create(Client $client): Client
    {
        $em = $this->getEntityManager();
        $user = $client->getUser();

        if ($em->getUnitOfWork()->getEntityState($user) !== UnitOfWork::STATE_MANAGED) {
            /** @var OAuthUser $user */
            $user = $em->getReference(OAuthUser::class, $user->getId());
            $client->setUser($user);
        }

        $em->persist($client);
        $em->flush();

        return $client;
    }

    public function save(Client $client): Client
    {
        $this->getEntityManager()->flush();

        return $client;
    }

    public function delete(Client $client): void
    {
        $this->getEntityManager()->remove($client);
        $this->getEntityManager()->flush();
    }

    public function validateClient($clientIdentifier, $clientSecret, $grantType): bool
    {
        /** @var Client $client */
        $client = $this->getClientEntity($clientIdentifier);

        return !($client->isConfidential() && $clientSecret !== $client->getSecret());
    }


}
