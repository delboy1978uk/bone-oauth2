<?php

namespace Bone\OAuth2\Command;

use Del\Service\UserService;
use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Repository\ScopeRepository;
use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Service\ClientService;
use Bone\OAuth2\Entity\OAuthUser as User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class ScopeListCommand extends Command
{
    public function __construct(
        private ScopeRepository $scopeRepository,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * configure options
     */
    protected function configure(): void
    {
        $this->setName('scope:list');
        $this->setDescription('Lists all scopes.');
        $this->setHelp('Lists available access scopes.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Listing scopes...');
        $scopes = $this->scopeRepository->findAll();

        if (!count($scopes)) {
            $output->writeln('No scopes found.');
        }

        /** @var Scope $scope */
        foreach ($scopes as $scope) {
            $output->writeln(' - ' . $scope->getIdentifier());
        }

        return Command::SUCCESS;
    }
}
