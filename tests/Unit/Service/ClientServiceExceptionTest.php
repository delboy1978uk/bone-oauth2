<?php

namespace Bone\OAuth2\Test\Unit\Service;

use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Exception\OAuthException;
use Bone\OAuth2\Repository\ClientRepository;
use Bone\OAuth2\Service\ClientService;
use Del\Entity\User;
use Codeception\Test\Unit;
use Doctrine\ORM\EntityManagerInterface;

class ClientServiceExceptionTest extends Unit
{
    public function testRegisterNewClientWithException()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $clientRepo = $this->createMock(ClientRepository::class);
        
        $service = new ClientService($clientRepo);
        
        $user = new User();
        $user->setId(1);
        
        $data = [
            'name' => 'Test Client',
            'description' => 'Test Description',
            'icon' => 'icon.png',
            'redirect_uri' => 'https://example.com/callback',
            'grant_type' => 'authorization_code',
            'confidential' => true,
            'scopes' => []
        ];
        
        // Mock the repository to throw an exception during save
        $clientRepo->expects($this->once())
            ->method('create')
            ->willReturn(new Client());
        
        $clientRepo->expects($this->once())
            ->method('save')
            ->willThrowException(new \Exception('Database error'));
        
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Error creating client');
        
        $service->registerNewClient($user, $data);
    }
}
