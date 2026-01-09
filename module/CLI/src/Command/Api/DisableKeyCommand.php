<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Api;

use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
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
    name: DisableKeyCommand::NAME,
    description: 'Disables an API key by name',
    help: <<<HELP
    The <info>%command.name%</info> command allows you to disable an existing API key.

    If no arguments are provided, you will be prompted to select one of the existing non-disabled API keys.

        <info>%command.full_name%</info>

    You can optionally pass the API key name to be disabled:

        <info>%command.full_name% the_key_name</info>

    HELP,
)]
class DisableKeyCommand extends Command
{
    public const string NAME = 'api-key:disable';

    public function __construct(private readonly ApiKeyServiceInterface $apiKeyService)
    {
        parent::__construct();
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $name = $input->getArgument('name');

        if ($name === null) {
            $apiKeys = $this->apiKeyService->listKeys(enabledOnly: true);
            $name = new SymfonyStyle($input, $output)->choice(
                'What API key do you want to disable?',
                map($apiKeys, static fn (ApiKey $apiKey) => $apiKey->name),
            );

            $input->setArgument('name', $name);
        }
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('The name of the API key to disable.')] string|null $name = null,
    ): int {
        if ($name === null) {
            $io->warning('An API key name was not provided.');
            return Command::INVALID;
        }

        try {
            $this->apiKeyService->disableByName($name);
            $io->success(sprintf('API key "%s" properly disabled', $name));
            return Command::SUCCESS;
        } catch (InvalidArgumentException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
