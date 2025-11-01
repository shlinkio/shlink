<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Api;

use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: InitialApiKeyCommand::NAME,
    description: 'Tries to create initial API key',
)]
class InitialApiKeyCommand extends Command
{
    public const string NAME = 'api-key:initial';

    public function __construct(private readonly ApiKeyServiceInterface $apiKeyService)
    {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('The initial API to create')] string $apiKey,
    ): int {
        $result = $this->apiKeyService->createInitial($apiKey);

        if ($result === null && $io->isVerbose()) {
            $io->writeln('<comment>Other API keys already exist. Initial API key creation skipped.</comment>');
        }

        return Command::SUCCESS;
    }
}
