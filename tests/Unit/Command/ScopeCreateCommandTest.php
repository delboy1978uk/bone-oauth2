<?php

declare(strict_types=1);

namespace Tests\Unit\Command;

use Bone\OAuth2\Command\ScopeCreateCommand;
use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Repository\ScopeRepository;
use Codeception\Test\Unit;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScopeCreateCommandTest extends Unit
{
    private ScopeCreateCommand $command;
    private ScopeRepository $scopeRepository;
    private InputInterface $input;
    private OutputInterface $output;
    private QuestionHelper $questionHelper;

    protected function _before()
    {
        $this->scopeRepository = $this->createMock(ScopeRepository::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);
        $this->questionHelper = $this->createMock(QuestionHelper::class);

        $this->command = new ScopeCreateCommand(
            $this->scopeRepository
        );
    }

    public function testCommandConfiguration()
    {
        $this->assertEquals('scope:create', $this->command->getName());
        $this->assertEquals('Creates a new scope.', $this->command->getDescription());
        $this->assertEquals('Create a new OAuth2 client application', $this->command->getHelp());
    }

    public function testExecuteCreatesScope()
    {
        // Mock question helper responses
        $this->questionHelper->method('ask')
            ->willReturnOnConsecutiveCalls(
                'read',                    // Scope name
                'Read access to resources' // Description
            );

        // Setup command helper
        $this->command->setHelperSet(
            new \Symfony\Component\Console\Helper\HelperSet([
                'question' => $this->questionHelper
            ])
        );

        $this->scopeRepository->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($scope) {
                return $scope instanceof Scope
                    && $scope->getIdentifier() === 'read'
                    && $scope->getDescription() === 'Read access to resources';
            }));

        $this->output->expects($this->atLeastOnce())
            ->method('writeln')
            ->withConsecutive(
                ['Bone API scope creator'],
                ['Scope created.']
            );

        $result = $this->command->run($this->input, $this->output);

        $this->assertEquals(Command::SUCCESS, $result);
    }

    public function testExecuteWithDifferentScopeData()
    {
        // Mock question helper responses with different data
        $this->questionHelper->method('ask')
            ->willReturnOnConsecutiveCalls(
                'write',                    // Scope name
                'Write access to resources' // Description
            );

        // Setup command helper
        $this->command->setHelperSet(
            new \Symfony\Component\Console\Helper\HelperSet([
                'question' => $this->questionHelper
            ])
        );

        $this->scopeRepository->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($scope) {
                return $scope instanceof Scope
                    && $scope->getIdentifier() === 'write'
                    && $scope->getDescription() === 'Write access to resources';
            }));

        $result = $this->command->run($this->input, $this->output);

        $this->assertEquals(Command::SUCCESS, $result);
    }

    public function testExecuteWithEmptyDescription()
    {
        // Mock question helper responses with empty description
        $this->questionHelper->method('ask')
            ->willReturnOnConsecutiveCalls(
                'admin',  // Scope name
                ''        // Empty description
            );

        // Setup command helper
        $this->command->setHelperSet(
            new \Symfony\Component\Console\Helper\HelperSet([
                'question' => $this->questionHelper
            ])
        );

        $this->scopeRepository->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($scope) {
                return $scope instanceof Scope
                    && $scope->getIdentifier() === 'admin'
                    && $scope->getDescription() === '';
            }));

        $result = $this->command->run($this->input, $this->output);

        $this->assertEquals(Command::SUCCESS, $result);
    }
}
