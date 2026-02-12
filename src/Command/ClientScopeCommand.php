<?php

declare(strict_types=1);

namespace Bone\OAuth2\Command;

use Doctrine\Common\Collections\Collection;
use Exception;
use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Repository\ScopeRepository;
use Bone\OAuth2\Entity\Scope;
use Bone\OAuth2\Service\ClientService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class ClientScopeCommand extends Command
{
    public function __construct(
        private ClientService $clientService,
        private ScopeRepository $scopeRepository
    ) {
        parent::__construct('client:scope');
        $this->addArgument('operation', InputArgument::REQUIRED, 'list, add, or remove.');
        $this->addArgument('client', InputArgument::OPTIONAL, 'The client identifier.');
        $this->addArgument('scope', InputArgument::OPTIONAL, 'The scope name when adding or removing.');
    }

    protected function configure(): void
    {
        $this->setDescription('Add, remove, or list scopes for each client.');
        $this->setHelp('Client scope administration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(' ');
        $output->writeln('Client scope administration');
        $operation = $input->getArgument('operation');

        switch ($operation) {
            case 'list';
                $this->listScopes($input, $output);
                break;
            case 'add';
                $this->addScope($input, $output);
                break;
            case 'remove';
                $this->removeScope($input, $output);
                break;
        }
        $output->writeln(' ');

        return Command::SUCCESS;
    }

    private function getArgOrGetUpset(InputInterface $input, string $argName): string
    {
        $value = $input->getArgument($argName);

        if (!$value) {
            throw new Exception('No ' . $argName . ' provided');
        }

        return $value;
    }

    private function listScopes(InputInterface $input, OutputInterface $output): void
    {
        $clientId = $input->getArgument('client');

        $client = $this->fetchClient($output, $clientId);
        if (!$client instanceof Client) {
            $output->writeln('No client found');
            return;
        }

        $scopes = $client->getScopes();
        $this->outputScopes($output, $client, $scopes);

        return;
    }

    /**
     * @param Collection<int, Scope> $scopes
     */
    private function outputScopes(OutputInterface $output, Client $client, Collection $scopes): void
    {
        $output->writeln('Listing scopes for ' . $client->getName() . '.');

        if ($scopes->count()) {
            /** @var Scope $scope */
            foreach ($scopes as $scope) {
                $output->writeln(' - ' . $scope->getIdentifier());
            }
        } else {
            $output->writeln('No scopes set for ' . $client->getName() . '.');
        }
    }

    private function fetchClient(OutputInterface $output, string $id): ?Client
    {
        $output->writeln('Fetching client ' . $id .'...');

        return $this->clientService->getClientRepository()->getClientEntity($id);
    }

    private function addScope(InputInterface $input, OutputInterface $output): void
    {
        $clientId = $input->getArgument('client');
        $scopeId = $this->getArgOrGetUpset($input, 'scope');
        $client = $this->fetchClient($output, $clientId);

        if (!$client instanceof Client) {
            $output->writeln('No client found');
            return;
        }

        $scope = $this->scopeRepository->getScopeEntityByIdentifier($scopeId);

        if (!$scope instanceof Scope) {
            $output->writeln('No scope found.');
            return;
        }

        $output->writeln('Adding '. $scopeId . ' scope to ' . $client->getName() . '...');
        $client->getScopes()->add($scope);
        $this->clientService->getClientRepository()->save($client);
        $output->writeln($scopeId . ' scope added.');
    }

    private function removeScope(InputInterface $input, OutputInterface $output): void
    {
        $clientId = $input->getArgument('client');
        $scopeId = $this->getArgOrGetUpset($input, 'scope');

        $client = $this->fetchClient($output, $clientId);
        if (!$client instanceof Client) {
            $output->writeln('No client found');
            return;
        }

        $scopes = $client->getScopes();
        /** @var Scope $scope */
        foreach ($scopes as $key => $scope) {
            if ($scope->getIdentifier() == $scopeId) {
                $scopes->remove($key);
                break;
            }
        }

        $this->clientService->getClientRepository()->save($client);
        $output->writeln($scopeId . ' scope removed.');
    }
}
