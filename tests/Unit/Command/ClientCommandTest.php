<?php

declare(strict_types=1);

namespace Tests\Unit\Command;

use Bone\OAuth2\Command\ClientCommand;
use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Repository\ClientRepository;
use Bone\OAuth2\Repository\ScopeRepository;
use Bone\OAuth2\Service\ClientService;
use Codeception\Test\Unit;
use Del\Entity\User;
use Del\Service\UserService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class ClientCommandTest extends Unit
{
    private ClientCommand $command;
    private ClientService $clientService;
    private UserService $userService;
    private ScopeRepository $scopeRepository;
    private InputInterface $input;
    private OutputInterface $output;
    private QuestionHelper $questionHelper;
    private ClientRepository $clientRepository;

    protected function _before()
    {
        $this->clientService = $this->createMock(ClientService::class);
        $this->userService = $this->createMock(UserService::class);
        $this->scopeRepository = $this->createMock(ScopeRepository::class);
        $this->clientRepository = $this->createMock(ClientRepository::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);
        $this->questionHelper = $this->createMock(QuestionHelper::class);

        $this->clientService->method('getClientRepository')
            ->willReturn($this->clientRepository);

        $this->command = new ClientCommand(
            $this->clientService,
            $this->userService,
            $this->scopeRepository
        );
    }

    public function testCommandConfiguration()
    {
        $this->assertEquals('client:create', $this->command->getName());
        $this->assertEquals('Creates a new client.', $this->command->getDescription());
        $this->assertEquals('Create a new OAuth2 client application', $this->command->getHelp());
    }

    public function testExecuteWithClientCredentialsGrant()
    {
        // Setup scopes
        $scope1 = new Scope();
        $scope1->setIdentifier('read');
        $scope2 = new Scope();
        $scope2->setIdentifier('write');
        $scopes = [$scope1, $scope2];

        $this->scopeRepository->method('findAll')
            ->willReturn($scopes);

        // Setup user
        $user = $this->createMock(User::class);
        $this->userService->method('findUserByEmail')
            ->with('test@example.com')
            ->willReturn($user);

        // Mock question helper responses
        $this->questionHelper->method('ask')
            ->willReturnOnConsecutiveCalls(
                true,  // Is client_credentials?
                false, // Is third party?
                'test@example.com', // User email
                'Test Client', // Name
                'Test Description', // Description
                'https://example.com/icon.png', // Icon
                'https://example.com/callback', // Redirect URI
                ['read', 'write'] // Scopes
            );

        // Setup command helper
        $this->command->setHelperSet(
            new \Symfony\Component\Console\Helper\HelperSet([
                'question' => $this->questionHelper
            ])
        );

        $this->clientService->expects($this->once())
            ->method('generateSecret');

        $this->clientRepository->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($client) {
                return $client instanceof Client
                    && $client->getName() === 'Test Client'
                    && $client->getGrantType() === 'client_credentials'
                    && $client->isConfidential() === true;
            }));

        $result = $this->command->run($this->input, $this->output);

        $this->assertEquals(Command::SUCCESS, $result);
    }

    public function testExecuteWithAuthCodeGrant()
    {
        // Setup scopes
        $scope1 = new Scope();
        $scope1->setIdentifier('read');
        $scopes = [$scope1];

        $this->scopeRepository->method('findAll')
            ->willReturn($scopes);

        // Mock question helper responses for auth_code grant
        $this->questionHelper->method('ask')
            ->willReturnOnConsecutiveCalls(
                false, // Is client_credentials? NO
                false, // Is third party? NO
                'Test Client', // Name
                'Test Description', // Description
                'https://example.com/icon.png', // Icon
                'https://example.com/callback', // Redirect URI
                ['read'] // Scopes
            );

        // Setup command helper
        $this->command->setHelperSet(
            new \Symfony\Component\Console\Helper\HelperSet([
                'question' => $this->questionHelper
            ])
        );

        $this->clientService->expects($this->never())
            ->method('generateSecret');

        $this->clientRepository->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($client) {
                return $client instanceof Client
                    && $client->getName() === 'Test Client'
                    && $client->getGrantType() === 'auth_code'
                    && $client->isConfidential() === false;
            }));

        $result = $this->command->run($this->input, $this->output);

        $this->assertEquals(Command::SUCCESS, $result);
    }

    public function testExecuteWithThirdPartyClient()
    {
        // Setup scopes
        $scope1 = new Scope();
        $scope1->setIdentifier('read');
        $scopes = [$scope1];

        $this->scopeRepository->method('findAll')
            ->willReturn($scopes);

        // Setup user
        $user = $this->createMock(User::class);
        $this->userService->method('findUserById')
            ->with(123)
            ->willReturn($user);

        // Mock question helper responses for third party
        $this->questionHelper->method('ask')
            ->willReturnOnConsecutiveCalls(
                false, // Is client_credentials? NO
                true,  // Is third party? YES
                '123', // User ID (numeric)
                'Third Party Client', // Name
                'Third Party Description', // Description
                'https://thirdparty.com/icon.png', // Icon
                'https://thirdparty.com/callback', // Redirect URI
                ['read'] // Scopes
            );

        // Setup command helper
        $this->command->setHelperSet(
            new \Symfony\Component\Console\Helper\HelperSet([
                'question' => $this->questionHelper
            ])
        );

        $this->clientRepository->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($client) {
                return $client instanceof Client
                    && $client->getName() === 'Third Party Client'
                    && $client->isProprietary() === false
                    && $client->getUser() !== null;
            }));

        $result = $this->command->run($this->input, $this->output);

        $this->assertEquals(Command::SUCCESS, $result);
    }

    public function testUserQuestionWithInvalidUserRetries()
    {
        // Setup scopes
        $scope1 = new Scope();
        $scope1->setIdentifier('read');
        $scopes = [$scope1];

        $this->scopeRepository->method('findAll')
            ->willReturn($scopes);

        // Setup user - first call returns null, second returns valid user
        $user = $this->createMock(User::class);
        $this->userService->method('findUserByEmail')
            ->willReturnOnConsecutiveCalls(
                null, // First attempt fails
                $user  // Second attempt succeeds
            );

        // Mock question helper responses
        $this->questionHelper->method('ask')
            ->willReturnOnConsecutiveCalls(
                true,  // Is client_credentials?
                false, // Is third party?
                'invalid@example.com', // Invalid user email (first attempt)
                'valid@example.com',   // Valid user email (second attempt)
                'Test Client', // Name
                'Test Description', // Description
                'https://example.com/icon.png', // Icon
                'https://example.com/callback', // Redirect URI
                ['read'] // Scopes
            );

        // Setup command helper
        $this->command->setHelperSet(
            new \Symfony\Component\Console\Helper\HelperSet([
                'question' => $this->questionHelper
            ])
        );

        $this->clientRepository->expects($this->once())
            ->method('create');

        $result = $this->command->run($this->input, $this->output);

        $this->assertEquals(Command::SUCCESS, $result);
    }
}
