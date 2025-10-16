<?php

declare(strict_types=1);

namespace Bone\OAuth2\Command;

use Del\Service\UserService;
use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Repository\ScopeRepository;
use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Service\ClientService;
use Del\Entity\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;


class ScopeCreateCommand extends Command
{
    public function __construct(
        private ScopeRepository $scopeRepository,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('scope:create');
        $this->setDescription('Creates a new scope.');
        $this->setHelp('Create a new OAuth2 client application');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Bone API scope creator');
        $helper = $this->getHelper('question');

        $question = new Question('Name of new scope: ', false);
        $scopeName = $helper->ask($input, $output, $question);

        $question = new Question('Describe this scope: ', false);
        $description = $helper->ask($input, $output, $question);

        $scope = new Scope();
        $scope->setIdentifier($scopeName);
        $scope->setDescription($description);

        $this->scopeRepository->create($scope);

        $output->writeln('Scope created.');

        return Command::SUCCESS;
    }
}
