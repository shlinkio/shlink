<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Api;

use Shlinkio\Shlink\Core\Model\Renaming;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Ask;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: RenameApiKeyCommand::NAME,
    description: 'Renames an API key by name',
)]
class RenameApiKeyCommand extends Command
{
    public const string NAME = 'api-key:rename';

    public function __construct(private readonly ApiKeyServiceInterface $apiKeyService)
    {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Current name of the API key to rename'), Ask('What API key do you want to rename?')]
        string $oldName,
        #[Argument(description: 'New name to set to the API key'), Ask('What is the new name you want to set?')]
        string $newName,
    ): int {
        $this->apiKeyService->renameApiKey(Renaming::fromNames($oldName, $newName));
        $io->success('API key properly renamed');

        return Command::SUCCESS;
    }
}
