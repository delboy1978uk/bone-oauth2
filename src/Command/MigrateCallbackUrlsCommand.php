<?php

declare(strict_types=1);

namespace Bone\OAuth2\Command;

use Bone\OAuth2\Entity\Client;
use Bone\OAuth2\Entity\ClientCallbackUrl;
use Bone\OAuth2\Repository\ClientRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateCallbackUrlsCommand extends Command
{
    protected static $defaultName = 'oauth2:migrate-callback-urls';

    public function __construct(
        private ClientRepository $clientRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Migrate existing clients from single redirectUri to multiple callback URLs')
            ->setHelp('This command migrates existing OAuth2 clients that have a redirectUri set to use the new ClientCallbackUrl entity.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('OAuth2 Callback URLs Migration');

        // Find all clients
        $clients = $this->clientRepository->findAll();
        $migratedCount = 0;
        $skippedCount = 0;

        $io->progressStart(count($clients));

        foreach ($clients as $client) {
            /** @var Client $client */
            $redirectUri = $client->getRedirectUri();
            
            // Skip if no redirectUri or already has callback URLs
            if (empty($redirectUri) || $client->getCallbackUrls()->count() > 0) {
                $skippedCount++;
                $io->progressAdvance();
                continue;
            }

            // Create new callback URL from existing redirectUri
            $callbackUrl = new ClientCallbackUrl();
            $callbackUrl->setUrl($redirectUri);
            $client->addCallbackUrl($callbackUrl);
            
            // Persist the changes
            $this->clientRepository->getEntityManager()->persist($client);
            $migratedCount++;
            $io->progressAdvance();
        }

        // Flush all changes
        $this->clientRepository->getEntityManager()->flush();
        $io->progressFinish();

        $io->success([
            "Migration completed!",
            "Migrated: {$migratedCount} clients",
            "Skipped: {$skippedCount} clients (already migrated or no redirectUri)"
        ]);

        return Command::SUCCESS;
    }
}
