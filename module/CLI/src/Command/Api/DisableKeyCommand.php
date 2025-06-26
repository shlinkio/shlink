<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Api;

use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function Shlinkio\Shlink\Core\ArrayUtils\map;
use function sprintf;

#[AsCommand(
    name: DisableKeyCommand::NAME,
    description: 'Disables an API key by name or plain-text key (providing a plain-text key is DEPRECATED)',
    help: <<<HELP
    The <info>%command.name%</info> command allows you to disable an existing API key, via its name or the
    plain-text key.

    If no arguments are provided, you will be prompted to select one of the existing non-disabled API keys.

        <info>%command.full_name%</info>

    You can optionally pass the API key name to be disabled. In that case <comment>--by-name</comment> is also
    required, to indicate the first argument is the API key name and not the plain-text key:

        <info>%command.full_name% the_key_name --by-name</info>

    You can pass the plain-text key to be disabled, but that is <options=bold>DEPRECATED</>. In next major version,
    the argument will always be assumed to be the name:

        <info>%command.full_name% d6b6c60e-edcd-4e43-96ad-fa6b7014c143</info>

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
        $keyOrName = $input->getArgument('key-or-name');

        if ($keyOrName === null) {
            $apiKeys = $this->apiKeyService->listKeys(enabledOnly: true);
            $name = (new SymfonyStyle($input, $output))->choice(
                'What API key do you want to disable?',
                map($apiKeys, static fn (ApiKey $apiKey) => $apiKey->name),
            );

            $input->setArgument('key-or-name', $name);
            $input->setOption('by-name', true);
        }
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(
            description: 'The API key to disable. Pass `--by-name` to indicate this value is the name and not the key.',
        )]
        string|null $keyOrName = null,
        #[Option(description: 'Indicates the first argument is the API key name, not the plain-text key.')]
        bool $byName = false,
    ): int {
        if ($keyOrName === null) {
            $io->warning('An API key name was not provided.');
            return Command::INVALID;
        }

        try {
            if ($byName) {
                $this->apiKeyService->disableByName($keyOrName);
            } else {
                $this->apiKeyService->disableByKey($keyOrName);
            }
            $io->success(sprintf('API key "%s" properly disabled', $keyOrName));
            return Command::SUCCESS;
        } catch (InvalidArgumentException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
