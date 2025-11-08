<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Api;

use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Exception\ApiKeyNotFoundException;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function Shlinkio\Shlink\Core\ArrayUtils\map;
use function sprintf;

#[AsCommand(
    name: DeleteKeyCommand::NAME,
    description: 'Deletes an API key by name',
    help: <<<HELP
    The <info>%command.name%</info> command allows you to delete an existing API key via its name.

    If no arguments are provided, you will be prompted to select one of the existing API keys.

        <info>%command.full_name%</info>

    You can optionally pass the API key name to be disabled:

        <info>%command.full_name% the_key_name</info>

    HELP,
)]
class DeleteKeyCommand extends Command
{
    public const string NAME = 'api-key:delete';

    public function __construct(private readonly ApiKeyServiceInterface $apiKeyService)
    {
        parent::__construct();
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $apiKeyName = $input->getArgument('name');

        if ($apiKeyName === null) {
            $apiKeys = $this->apiKeyService->listKeys();
            $name = new SymfonyStyle($input, $output)->choice(
                'What API key do you want to delete?',
                map($apiKeys, static fn (ApiKey $apiKey) => $apiKey->name),
            );

            $input->setArgument('name', $name);
        }
    }

    public function __invoke(
        SymfonyStyle $io,
        InputInterface $input,
        #[Argument(description: 'The API key to delete.')]
        string|null $name = null,
    ): int {
        if ($name === null) {
            $io->warning('An API key name was not provided.');
            return Command::INVALID;
        }

        if (! $this->shouldProceed($io, $input)) {
            return Command::INVALID;
        }

        try {
            $this->apiKeyService->deleteByName($name);
            $io->success(sprintf('API key "%s" properly deleted', $name));
            return Command::SUCCESS;
        } catch (ApiKeyNotFoundException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function shouldProceed(SymfonyStyle $io, InputInterface $input): bool
    {
        if (! $input->isInteractive()) {
            return true;
        }

        $io->warning('You are about to delete an API key. This action cannot be undone.');
        return $io->confirm('Are you sure you want to delete the API key?');
    }
}
