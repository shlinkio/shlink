<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Api;

use Shlinkio\Shlink\CLI\ApiKey\RoleResolverInterface;
use Shlinkio\Shlink\CLI\Command\Api\Input\ApiKeyInput;
use Shlinkio\Shlink\CLI\Util\ShlinkTable;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Role;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function Shlinkio\Shlink\Core\arrayToString;
use function Shlinkio\Shlink\Core\normalizeOptionalDate;
use function sprintf;

#[AsCommand(
    name: GenerateKeyCommand::NAME,
    description: 'Generate a new valid API key',
    help: <<<HELP
        The <info>%command.name%</info> generates a new valid API key.

            <info>%command.full_name%</info>

        You can optionally set its name for tracking purposes with <comment>--name</comment> or <comment>-m</comment>:

            <info>%command.full_name% --name Alice</info>

        You can optionally set its expiration date with <comment>--expiration-date</comment> or <comment>-e</comment>:

            <info>%command.full_name% --expiration-date 2020-01-01</info>
        HELP,
)]
class GenerateKeyCommand extends Command
{
    public const string NAME = 'api-key:generate';

    public function __construct(
        private readonly ApiKeyServiceInterface $apiKeyService,
        private readonly RoleResolverInterface $roleResolver,
    ) {
        parent::__construct();
    }

    public function __invoke(SymfonyStyle $io, InputInterface $input, #[MapInput] ApiKeyInput $inputData): int
    {
        $expirationDate = $inputData->expirationDate;
        $apiKeyMeta = ApiKeyMeta::fromParams(
            name: $inputData->name,
            expirationDate: isset($expirationDate) ? normalizeOptionalDate($expirationDate) : null,
            roleDefinitions: $this->roleResolver->determineRoles($inputData),
        );

        $apiKey = $this->apiKeyService->create($apiKeyMeta);
        $io->success(sprintf('Generated API key: "%s"', $apiKeyMeta->key));

        if ($input->isInteractive()) {
            $io->warning('Save the key in a secure location. You will not be able to get it afterwards.');
        }

        if (! ApiKey::isAdmin($apiKey)) {
            ShlinkTable::default($io)->render(
                ['Role name', 'Role metadata'],
                $apiKey->mapRoles(fn (Role $role, array $meta) => [$role->value, arrayToString($meta, indentSize: 0)]),
                headerTitle: 'Roles',
            );
        }

        return Command::SUCCESS;
    }
}
