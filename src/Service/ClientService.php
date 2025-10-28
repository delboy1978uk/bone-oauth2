<?php

declare(strict_types=1);

namespace Bone\OAuth2\Service;

use Bone\OAuth2\Entity\Client;
use Del\Entity\User;
use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Form\RegisterClientForm;
use Bone\OAuth2\Repository\ClientRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;

class ClientService
{
    public function __construct(
        private ClientRepository $clientRepository
    ) {
    }

    public function getClientRepository(): ClientRepository
    {
        return $this->clientRepository;
    }

    public function generateSecret(Client $client): Client
    {
        $time = microtime();
        $name = $client->getName();
        $secret = password_hash($name . $time  . 'bone', PASSWORD_BCRYPT);
        $base64 = base64_encode($secret);
        $client->setSecret($base64);

        return $client;
    }

    public function deleteClient(Client $client): void
    {
        $this->getClientRepository()->delete($client);
    }

    /**
     * @param array<string,string|bool> $data
     */
    public function createFromArray(array $data, User $user): Client
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

    public function registerNewClient(RegisterClientForm $form): ResponseInterface
    {
        if ($form->isValid()) {
            $formData = $form->getValues();
            $data = [
                'name' => $formData['client_name'] . microtime(),
                'description' => 'auto registered client',
                'redirectUri' => $formData['redirect_uris'],
                'grantType' => 'auth_code',
                'icon' => $formData['logo_uri'],
                'confidential' => false,
            ];

            $user = $this->getClientRepository()->getEntityManager()->getRepository(User::class)->find(1);
            $scope = $this->getClientRepository()->getEntityManager()->getRepository(Scope::class)->findOneBy(['identifier' => 'basic']);
            $client = $this->createFromArray($data, $user);
            $client->setScopes(new ArrayCollection([$scope]));
            $this->getClientRepository()->create($client);
            $now = new DateTime();

            $body = [
                'client_id' => $client->getIdentifier(),
                'client_secret' => $client->getSecret(),
                'client_id_issued_at' => $now->format('Y-m-d\TH:i:s\Z'),
            ];
            $code = 200;
        } else {
            $body = [];
            $errors = $form->getErrorMessages();

            foreach ($errors as $field => $fieldErrors) {
                $body['error'] = 'Invalid request.';
                $body['error_description'] = $field . ' - ' . $fieldErrors[0];
                break;
            }

            $code = 400;
        }

        $response = new JsonResponse($body);

        return $response->withStatus($code);
    }
}
