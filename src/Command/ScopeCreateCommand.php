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

/**
 * Class ClientCommand
 * @package OAuth\Command
 */
class ScopeCreateCommand extends Command
{
    /**
     * @var ScopeRepository $scopeRepository
     */
    private $scopeRepository;

    /**
     * ScopeCreateCommand constructor.
     * @param ScopeRepository $scopeRepository
     * @param string|null $name
     */
    public function __construct(ScopeRepository $scopeRepository, ?string $name = null)
    {
        $this->scopeRepository = $scopeRepository;
        parent::__construct($name);
    }

    /**
     * configure options
     */
    protected function configure()
    {
        $this->setName('scope:create');
        $this->setDescription('Creates a new scope.');
        $this->setHelp('Create a new OAuth2 client application');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
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

        return 0;
    }
}