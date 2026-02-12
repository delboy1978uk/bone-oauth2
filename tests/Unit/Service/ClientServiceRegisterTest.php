<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Repository\ClientRepository;
use Bone\OAuth2\Service\ClientService;
use Codeception\Test\Unit;
use Del\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;

class ClientServiceRegisterTest extends Unit
{
    private ClientService $service;
    private ClientRepository $repository;

    protected function _before()
    {
        $this->repository = $this->createMock(ClientRepository::class);
        $this->service = new ClientService($this->repository);
    }

    public function testRegisterNewClientWithAllFields()
    {
        $user = $this->createMock(User::class);
        $scope1 = new Scope();
        $scope1->setIdentifier('read');
        $scope2 = new Scope();
        $scope2->setIdentifier('write');

        $data = [
            'name' => 'Test Client',
            'description' => 'Test Description',
            'icon' => 'https://example.com/icon.png',
            'redirect_uri' => 'https://example.com/callback',
            'grant_type' => 'authorization_code',
            'confidential' => true,
            'proprietary' => false,
            'user' => $user,
            'scopes' => [$scope1, $scope2]
        ];

        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($client) use ($user) {
                return $client instanceof Client
                    && $client->getName() === 'Test Client'
                    && $client->getDescription() === 'Test Description'
                    && $client->getIcon() === 'https://example.com/icon.png'
                    && $client->getRedirectUri() === 'https://example.com/callback'
                    && $client->getGrantType() === 'authorization_code'
                    && $client->isConfidential() === true
                    && $client->isProprietary() === false
                    && $client->getUser() === $user
                    && $client->getScopes()->count() === 2
                    && $client->getSecret() !== null;
            }));

        $result = $this->service->registerNewClient($data);

        $this->assertInstanceOf(Client::class, $result);
        $this->assertEquals('Test Client', $result->getName());
    }

    public function testRegisterNewClientWithMinimalFields()
    {
        $data = [
            'name' => 'Minimal Client',
            'redirect_uri' => 'https://example.com/callback',
            'grant_type' => 'client_credentials'
        ];

        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($client) {
                return $client instanceof Client
                    && $client->getName() === 'Minimal Client'
                    && $client->getRedirectUri() === 'https://example.com/callback'
                    && $client->getGrantType() === 'client_credentials';
            }));

        $result = $this->service->registerNewClient($data);

        $this->assertInstanceOf(Client::class, $result);
    }

    public function testRegisterNewClientGeneratesIdentifier()
    {
        $data = [
            'name' => 'Test Client',
            'redirect_uri' => 'https://example.com/callback',
            'grant_type' => 'authorization_code'
        ];

        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($client) {
                return $client instanceof Client
                    && $client->getIdentifier() !== null
                    && strlen($client->getIdentifier()) === 32;
            }));

        $result = $this->service->registerNewClient($data);

        $this->assertNotNull($result->getIdentifier());
    }

    public function testRegisterNewClientWithConfidentialGeneratesSecret()
    {
        $data = [
            'name' => 'Confidential Client',
            'redirect_uri' => 'https://example.com/callback',
            'grant_type' => 'client_credentials',
            'confidential' => true
        ];

        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($client) {
                return $client instanceof Client
                    && $client->isConfidential() === true
                    && $client->getSecret() !== null;
            }));

        $result = $this->service->registerNewClient($data);

        $this->assertNotNull($result->getSecret());
    }

    public function testRegisterNewClientWithNonConfidentialNoSecret()
    {
        $data = [
            'name' => 'Public Client',
            'redirect_uri' => 'https://example.com/callback',
            'grant_type' => 'authorization_code',
            'confidential' => false
        ];

        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($client) {
                return $client instanceof Client
                    && $client->isConfidential() === false
                    && $client->getSecret() === null;
            }));

        $result = $this->service->registerNewClient($data);

        $this->assertNull($result->getSecret());
    }
}
