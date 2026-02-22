<?php

declare(strict_types=1);

namespace Tests\Unit\Command;

use Bone\OAuth2\Command\ScopeListCommand;
use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Repository\ScopeRepository;
use Codeception\Test\Unit;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScopeListCommandTest extends Unit
{
    private ScopeListCommand $command;
    private ScopeRepository $scopeRepository;
    private InputInterface $input;
    private OutputInterface $output;

    protected function _before()
    {
        $this->scopeRepository = $this->createMock(ScopeRepository::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);

        $this->command = new ScopeListCommand(
            $this->scopeRepository
        );
    }

    public function testCommandConfiguration()
    {
        $this->assertEquals('scope:list', $this->command->getName());
        $this->assertEquals('Lists all scopes.', $this->command->getDescription());
        $this->assertEquals('Lists available access scopes.', $this->command->getHelp());
    }

    public function testExecuteWithScopes()
    {
        $scope1 = new Scope();
        $scope1->setIdentifier('read');
        
        $scope2 = new Scope();
        $scope2->setIdentifier('write');
        
        $scope3 = new Scope();
        $scope3->setIdentifier('admin');

        $scopes = [$scope1, $scope2, $scope3];

        $this->scopeRepository->method('findAll')
            ->willReturn($scopes);

        $this->output->expects($this->exactly(4))
            ->method('writeln')
            ->withConsecutive(
                ['Listing scopes...'],
                [' - read'],
                [' - write'],
                [' - admin']
            );

        $result = $this->command->run($this->input, $this->output);

        $this->assertEquals(Command::SUCCESS, $result);
    }

    public function testExecuteWithNoScopes()
    {
        $this->scopeRepository->method('findAll')
            ->willReturn([]);

        $this->output->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                ['Listing scopes...'],
                ['No scopes found.']
            );

        $result = $this->command->run($this->input, $this->output);

        $this->assertEquals(Command::SUCCESS, $result);
    }

    public function testExecuteWithSingleScope()
    {
        $scope = new Scope();
        $scope->setIdentifier('read');

        $this->scopeRepository->method('findAll')
            ->willReturn([$scope]);

        $this->output->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                ['Listing scopes...'],
                [' - read']
            );

        $result = $this->command->run($this->input, $this->output);

        $this->assertEquals(Command::SUCCESS, $result);
    }
}
