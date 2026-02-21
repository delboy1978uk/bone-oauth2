<?php

namespace Bone\OAuth2\Test\Unit\Service;

use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Repository\ClientRepository;
use Bone\OAuth2\Service\ClientService;
use Del\Entity\User;
use Bone\OAuth2\Form\RegisterClientForm;
use Codeception\Test\Unit;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class ClientServiceExceptionTest extends Unit
{
    public function testRegisterNewClientWithException()
    {
        $clientRepo = $this->createMock(ClientRepository::class);
        $service = new ClientService($clientRepo);

        $form = $this->createMock(RegisterClientForm::class);
        $form->method('isValid')->willReturn(false);
        $form->method('getErrorMessages')->willReturn(['field' => ['error']]);

        $response = $service->registerNewClient($form);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
    }
}
