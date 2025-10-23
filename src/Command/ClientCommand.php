<?php

declare(strict_types=1);

namespace Bone\OAuth2\Command;

use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Repository\ScopeRepository;
use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Service\ClientService;
use Del\Entity\User;
use Del\Service\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class ClientCommand extends Command
{
    private ?QuestionHelper $helper = null;
    private ?User $user = null;

    public function __construct(
        private ClientService $clientService,
        private UserService $userService,
        private ScopeRepository $scopeRepository
    ) {
        parent::__construct('client:create');
    }

    protected function configure(): void
    {
        $this->setDescription('Creates a new client.');
        $this->setHelp('Create a new OAuth2 client application');
    }

    private function userQuestion(InputInterface $input, OutputInterface $output): void
    {
        $question = new Question('If this API key will belong to a user, enter the email or ID of the account: ', false);
        $emailOrId = $this->helper->ask($input, $output, $question);

        if ($emailOrId !== false) {
            $this->user = \is_numeric($emailOrId)
                ? $this->userService->findUserById($emailOrId)
                : $this->userService->findUserByEmail($emailOrId);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Bone API client creator');
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $this->helper = $helper;

            $this->userQuestion($input, $output);

        $question = new ConfirmationQuestion('Is this a machine only (client_credentials) API key? ', false);
        $isClientCredentials = $this->helper->ask($input, $output, $question);
        $authGrant = $isClientCredentials ? 'client_credentials' : 'auth_code';
        $confidential = $isClientCredentials ? true : false;
        $output->writeln('Setting GrantType to ' . $authGrant . '..');
        $output->writeln('Setting confidential to ' . ($confidential ? 'true' : 'false') . '..');

        $question = new Question('Give a name for this application: ', false);
        $name = $this->helper->ask($input, $output, $question);

        $question = new Question('Give a description: ', false);
        $description = $this->helper->ask($input, $output, $question);

        $question = new Question('Give an icon URL: ', false);
        $icon = $this->helper->ask($input, $output, $question);

        $question = new Question('Give a redirect URI: ', '');
        $uri = $this->helper->ask($input, $output, $question);

        $scopes = $this->scopeRepository->findAll();
        $choices = [];

        foreach($scopes as $scope) {
            $scopeName = $scope->getIdentifier();
            $choices[] = $scopeName;
            $scopes[$scopeName] = $scope;
        }

        $question = new ChoiceQuestion('Which scopes would you like to add?', $choices);
        $question->setMultiselect(true);
        $scopeChoices = $this->helper->ask($input, $output, $question);

        $client = new Client();
        $client->setName($name);
        $client->setDescription($description);
        $client->setIcon($icon);
        $client->setGrantType($authGrant);
        $client->setIdentifier(md5($name));
        $client->setRedirectUri($uri);
        $client->setConfidential($confidential);

        if ($this->user !== null) {
            $client->setUser($this->user);
            $client->setProprietary(true);
        }

        foreach ($scopeChoices as $name) {
            $output->writeln('Registering ' . $name . ' scope with client..');
            $scope = $scopes[$name];
            $client->getScopes()->add($scope);
        }

        if ($confidential) {
            $output->writeln('Generating client secret..');
            $this->clientService->generateSecret($client);
        }

        $this->clientService->getClientRepository()->create($client);
        $output->writeln('Client created.');

        return Command::SUCCESS;
    }
}
