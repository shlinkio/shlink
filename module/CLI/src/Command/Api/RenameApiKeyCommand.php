<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Api;

use Shlinkio\Shlink\CLI\Util\ExitCode;
use Shlinkio\Shlink\Core\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Core\Model\Renaming;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function Shlinkio\Shlink\Core\ArrayUtils\map;

class RenameApiKeyCommand extends Command
{
    public const string NAME = 'api-key:rename';

    public function __construct(private readonly ApiKeyServiceInterface $apiKeyService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Renames an API key by name')
            ->addArgument('oldName', InputArgument::REQUIRED, 'Current name of the API key to rename')
            ->addArgument('newName', InputArgument::REQUIRED, 'New name to set to the API key');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        $oldName = $input->getArgument('oldName');
        $newName = $input->getArgument('newName');

        if ($oldName === null) {
            $apiKeys = $this->apiKeyService->listKeys();
            $requestedOldName = $io->choice(
                'What API key do you want to rename?',
                map($apiKeys, static fn (ApiKey $apiKey) => $apiKey->name),
            );

            $input->setArgument('oldName', $requestedOldName);
        }

        if ($newName === null) {
            $requestedNewName = $io->ask(
                'What is the new name you want to set?',
                validator: static fn (string|null $value): string => $value !== null
                    ? $value
                    : throw new InvalidArgumentException('The new name cannot be empty'),
            );

            $input->setArgument('newName', $requestedNewName);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $oldName = $input->getArgument('oldName');
        $newName = $input->getArgument('newName');

        $this->apiKeyService->renameApiKey(Renaming::fromNames($oldName, $newName));
        $io->success('API key properly renamed');

        return ExitCode::EXIT_SUCCESS;
    }
}
