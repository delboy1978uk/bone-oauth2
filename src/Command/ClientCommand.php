<?php

declare(strict_types=1);

namespace Bone\OAuth2\Command;

use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Repository\ScopeRepository;
use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Service\ClientService;
use Bone\OAuth2\Entity\OAuthUser as User;
use Del\Criteria\UserCriteria;
use Del\Service\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperInterface;
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
        $question = new Question('Enter the email of the account: ', false);
        $email = $this->helper->ask($input, $output, $question);
        /** @var User $user */
        $user = $this->userService->findUserByEmail($email);
        $this->user = $user;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Bone API client creator');
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $this->helper = $helper;

            $this->userQuestion($input, $output);

        if (!$this->user) {
            $output->writeln('User not found. Exiting.');
            return Command::FAILURE;
        }

        $question = new ConfirmationQuestion('Is this a machine only (client_credentials) API key? ', false);
        $isClientCredentials = $this->helper->ask($input, $output, $question);
        $usePKCE = false;

        if ($isClientCredentials) {
            $authGrant = 'client_credentials';
            $confidential = true;
        } else {

            $question = new ChoiceQuestion('What type of app is this? ', [
                'browser', 'server', 'native'
            ]);
            $clientType = $this->helper->ask($input, $output, $question);

            $question = new ConfirmationQuestion('Is this a trusted first party app? ', true);
            $confidential = (int) $this->helper->ask($input, $output, $question);
            $authGrant = 'auth_code';

            switch ($clientType) {
                case 'server':
                    break;
                case 'native':
                case 'browser':
                    $authGrant = 'auth_code';
                    $usePKCE = true;
                    break;
            }
        }

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
        /** @var Scope $scope */
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
        $client->setUser($this->user);

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
