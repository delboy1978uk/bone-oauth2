<?php

declare(strict_types=1);

namespace Tests\Unit\Command;

use Bone\OAuth2\Command\ClientScopeCommand;
use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Repository\ClientRepository;
use Bone\OAuth2\Repository\ScopeRepository;
use Bone\OAuth2\Service\ClientService;
use Codeception\Test\Unit;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClientScopeCommandTest extends Unit
{
    private ClientScopeCommand $command;
    private ClientService $clientService;
    private ScopeRepository $scopeRepository;
    private ClientRepository $clientRepository;
    private InputInterface $input;
    private OutputInterface $output;

    protected function _before()
    {
        $this->clientService = $this->createMock(ClientService::class);
        $this->scopeRepository = $this->createMock(ScopeRepository::class);
        $this->clientRepository = $this->createMock(ClientRepository::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);

        $this->clientService->method('getClientRepository')
            ->willReturn($this->clientRepository);

        $this->command = new ClientScopeCommand(
            $this->clientService,
            $this->scopeRepository
        );
    }

    public function testCommandConfiguration()
    {
        $this->assertEquals('client:scope', $this->command->getName());
        $this->assertEquals('Add, remove, or list scopes for each client.', $this->command->getDescription());
        $this->assertEquals('Client scope administration', $this->command->getHelp());
    }

    public function testExecuteListOperation()
    {
        $client = new Client();
        $client->setName('Test Client');
        $client->setIdentifier('test-client');
        
        $scope1 = new Scope();
        $scope1->setIdentifier('read');
        $scope2 = new Scope();
        $scope2->setIdentifier('write');
        
        $scopes = new ArrayCollection([$scope1, $scope2]);
        $client->setScopes($scopes);

        $this->input->method('getArgument')
            ->willReturnMap([
                ['operation', 'list'],
                ['client', 'test-client'],
            ]);

        $this->clientRepository->method('getClientEntity')
            ->with('test-client')
            ->willReturn($client);

        // Replace withConsecutive() with willReturnCallback()
        $invokedCount = $this->exactly(7);
        $this->output->expects($invokedCount)
            ->method('writeln')
            ->willReturnCallback(function ($parameters) use ($invokedCount) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertStringContainsString(' ', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertStringContainsString('Client scope administration', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 3) {
                    $this->assertStringContainsString('Fetching client', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 4) {
                    $this->assertStringContainsString('Listing scopes for Test Client', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 5) {
                    $this->assertEquals(' - read', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 6) {
                    $this->assertEquals(' - write', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 7) {
                    $this->assertStringContainsString(' ', $parameters);
                    return null;
                }
            });

        $result = $this->command->run($this->input, $this->output);

        $this->assertEquals(Command::SUCCESS, $result);
    }

    public function testExecuteListOperationWithNoScopes()
    {
        $client = new Client();
        $client->setName('Test Client');
        $client->setIdentifier('test-client');
        $client->setScopes(new ArrayCollection());

        $this->input->method('getArgument')
            ->willReturnMap([
                ['operation', 'list'],
                ['client', 'test-client'],
            ]);

        $this->clientRepository->method('getClientEntity')
            ->with('test-client')
            ->willReturn($client);

        // Replace withConsecutive() with willReturnCallback()
        $invokedCount = $this->exactly(6);
        $this->output->expects($invokedCount)
            ->method('writeln')
            ->willReturnCallback(function ($parameters) use ($invokedCount) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertStringContainsString(' ', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertStringContainsString('Client scope administration', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 3) {
                    $this->assertStringContainsString('Fetching client', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 4) {
                    $this->assertStringContainsString('Listing scopes for Test Client', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 5) {
                    $this->assertStringContainsString('No scopes set', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 6) {
                    $this->assertStringContainsString(' ', $parameters);
                    return null;
                }
            });

        $result = $this->command->run($this->input, $this->output);

        $this->assertEquals(Command::SUCCESS, $result);
    }

    public function testExecuteListOperationWithNoClient()
    {
        $this->input->method('getArgument')
            ->willReturnMap([
                ['operation', 'list'],
                ['client', 'non-existent'],
            ]);

        $this->clientRepository->method('getClientEntity')
            ->with('non-existent')
            ->willReturn(null);

        // Replace withConsecutive() with willReturnCallback()
        $invokedCount = $this->exactly(5);
        $this->output->expects($invokedCount)
            ->method('writeln')
            ->willReturnCallback(function ($parameters) use ($invokedCount) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertStringContainsString(' ', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertStringContainsString('Client scope administration', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 3) {
                    $this->assertStringContainsString('Fetching client', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 4) {
                    $this->assertStringContainsString('No client found', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 5) {
                    $this->assertStringContainsString(' ', $parameters);
                    return null;
                }
            });

        $result = $this->command->run($this->input, $this->output);

        $this->assertEquals(Command::SUCCESS, $result);
    }

    public function testExecuteAddOperation()
    {
        $client = new Client();
        $client->setName('Test Client');
        $client->setIdentifier('test-client');
        $client->setScopes(new ArrayCollection());

        $scope = new Scope();
        $scope->setIdentifier('read');

        $this->input->method('getArgument')
            ->willReturnMap([
                ['operation', 'add'],
                ['client', 'test-client'],
                ['scope', 'read'],
            ]);

        $this->clientRepository->method('getClientEntity')
            ->with('test-client')
            ->willReturn($client);

        $this->scopeRepository->method('getScopeEntityByIdentifier')
            ->with('read')
            ->willReturn($scope);

        $this->clientRepository->expects($this->once())
            ->method('save')
            ->with($client);

        // Replace withConsecutive() with willReturnCallback()
        $invokedCount = $this->exactly(6);
        $this->output->expects($invokedCount)
            ->method('writeln')
            ->willReturnCallback(function ($parameters) use ($invokedCount) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertStringContainsString(' ', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertStringContainsString('Client scope administration', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 3) {
                    $this->assertStringContainsString('Fetching client', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 4) {
                    $this->assertStringContainsString('Adding read scope', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 5) {
                    $this->assertStringContainsString('read scope added', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 6) {
                    $this->assertStringContainsString(' ', $parameters);
                    return null;
                }
            });

        $result = $this->command->run($this->input, $this->output);

        $this->assertEquals(Command::SUCCESS, $result);
        $this->assertTrue($client->getScopes()->contains($scope));
    }

    public function testExecuteAddOperationWithNoClient()
    {
        $this->input->method('getArgument')
            ->willReturnMap([
                ['operation', 'add'],
                ['client', 'non-existent'],
                ['scope', 'read'],
            ]);

        $this->clientRepository->method('getClientEntity')
            ->with('non-existent')
            ->willReturn(null);

        // Replace withConsecutive() with willReturnCallback()
        $invokedCount = $this->exactly(5);
        $this->output->expects($invokedCount)
            ->method('writeln')
            ->willReturnCallback(function ($parameters) use ($invokedCount) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertStringContainsString(' ', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertStringContainsString('Client scope administration', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 3) {
                    $this->assertStringContainsString('Fetching client', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 4) {
                    $this->assertStringContainsString('No client found', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 5) {
                    $this->assertStringContainsString(' ', $parameters);
                    return null;
                }
            });

        $result = $this->command->run($this->input, $this->output);

        $this->assertEquals(Command::SUCCESS, $result);
    }

    public function testExecuteAddOperationWithNoScope()
    {
        $client = new Client();
        $client->setName('Test Client');
        $client->setIdentifier('test-client');

        $this->input->method('getArgument')
            ->willReturnMap([
                ['operation', 'add'],
                ['client', 'test-client'],
                ['scope', 'non-existent'],
            ]);

        $this->clientRepository->method('getClientEntity')
            ->with('test-client')
            ->willReturn($client);

        $this->scopeRepository->method('getScopeEntityByIdentifier')
            ->with('non-existent')
            ->willReturn(null);

        // Replace withConsecutive() with willReturnCallback()
        $invokedCount = $this->exactly(5);
        $this->output->expects($invokedCount)
            ->method('writeln')
            ->willReturnCallback(function ($parameters) use ($invokedCount) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertStringContainsString(' ', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertStringContainsString('Client scope administration', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 3) {
                    $this->assertStringContainsString('Fetching client', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 4) {
                    $this->assertStringContainsString('No scope found.', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 5) {
                    $this->assertStringContainsString(' ', $parameters);
                    return null;
                }
            });

        $result = $this->command->run($this->input, $this->output);

        $this->assertEquals(Command::SUCCESS, $result);
    }

    public function testExecuteAddOperationWithMissingScopeArgument()
    {
        $this->input->method('getArgument')
            ->willReturnMap([
                ['operation', 'add'],
                ['client', 'test-client'],
                ['scope', null],
            ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No scope provided');

        $this->command->run($this->input, $this->output);
    }

    public function testExecuteRemoveOperation()
    {
        $client = new Client();
        $client->setName('Test Client');
        $client->setIdentifier('test-client');
        
        $scope = new Scope();
        $scope->setIdentifier('read');
        $client->setScopes(new ArrayCollection([$scope]));

        $this->input->method('getArgument')
            ->willReturnMap([
                ['operation', 'remove'],
                ['client', 'test-client'],
                ['scope', 'read'],
            ]);

        $this->clientRepository->method('getClientEntity')
            ->with('test-client')
            ->willReturn($client);

        $this->clientRepository->expects($this->once())
            ->method('save')
            ->with($client);

        // Replace withConsecutive() with willReturnCallback()
        $invokedCount = $this->exactly(5);
        $this->output->expects($invokedCount)
            ->method('writeln')
            ->willReturnCallback(function ($parameters) use ($invokedCount) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertStringContainsString(' ', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertStringContainsString('Client scope administration', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 3) {
                    $this->assertStringContainsString('Fetching client', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 4) {
                    $this->assertStringContainsString('read scope removed', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 5) {
                    $this->assertStringContainsString(' ', $parameters);
                    return null;
                }
            });

        $result = $this->command->run($this->input, $this->output);

        $this->assertEquals(Command::SUCCESS, $result);
        $this->assertFalse($client->getScopes()->contains($scope));
    }

    public function testExecuteRemoveOperationWithNoClient()
    {
        $this->input->method('getArgument')
            ->willReturnMap([
                ['operation', 'remove'],
                ['client', 'non-existent'],
                ['scope', 'read'],
            ]);

        $this->clientRepository->method('getClientEntity')
            ->with('non-existent')
            ->willReturn(null);

        // Replace withConsecutive() with willReturnCallback()
        $invokedCount = $this->exactly(5);
        $this->output->expects($invokedCount)
            ->method('writeln')
            ->willReturnCallback(function ($parameters) use ($invokedCount) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertStringContainsString(' ', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertStringContainsString('Client scope administration', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 3) {
                    $this->assertStringContainsString('Fetching client', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 4) {
                    $this->assertStringContainsString('No client found', $parameters);
                    return null;
                }
                if ($invokedCount->numberOfInvocations() === 5) {
                    $this->assertStringContainsString(' ', $parameters);
                    return null;
                }
            });

        $result = $this->command->run($this->input, $this->output);

        $this->assertEquals(Command::SUCCESS, $result);
    }

    public function testExecuteRemoveOperationWithMissingScopeArgument()
    {
        $this->input->method('getArgument')
            ->willReturnMap([
                ['operation', 'remove'],
                ['client', 'test-client'],
                ['scope', null],
            ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No scope provided');

        $this->command->run($this->input, $this->output);
    }
}
