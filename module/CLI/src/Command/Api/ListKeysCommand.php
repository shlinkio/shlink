<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Api;

use Shlinkio\Shlink\CLI\Util\ShlinkTable;
use Shlinkio\Shlink\Rest\ApiKey\Role;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_filter;
use function array_map;
use function implode;
use function sprintf;

#[AsCommand(
    name: ListKeysCommand::NAME,
    description: 'Lists all the available API keys.',
)]
class ListKeysCommand extends Command
{
    private const string ERROR_STRING_PATTERN = '<fg=red>%s</>';
    private const string SUCCESS_STRING_PATTERN = '<info>%s</info>';
    private const string WARNING_STRING_PATTERN = '<comment>%s</comment>';

    public const string NAME = 'api-key:list';

    public function __construct(private readonly ApiKeyServiceInterface $apiKeyService)
    {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(
            description: 'Tells if only enabled API keys should be returned.',
            shortcut: 'e',
        )]
        bool $enabledOnly = false,
    ): int {
        $rows = array_map(function (ApiKey $apiKey) use ($enabledOnly) {
            $expiration = $apiKey->expirationDate;
            $messagePattern = $this->determineMessagePattern($apiKey);

            // Set columns for this row
            $rowData = [sprintf($messagePattern, $apiKey->name ?? '-')];
            if (! $enabledOnly) {
                $rowData[] = sprintf($messagePattern, $this->getEnabledSymbol($apiKey));
            }
            $rowData[] = $expiration?->toAtomString() ?? '-';
            $rowData[] = ApiKey::isAdmin($apiKey) ? 'Admin' : implode("\n", $apiKey->mapRoles(
                fn (Role $role, array $meta) => $role->toFriendlyName($meta),
            ));

            return $rowData;
        }, $this->apiKeyService->listKeys($enabledOnly));

        ShlinkTable::withRowSeparators($io)->render(array_filter([
            'Name',
            ! $enabledOnly ? 'Is enabled' : null,
            'Expiration date',
            'Roles',
        ]), $rows);

        return Command::SUCCESS;
    }

    private function determineMessagePattern(ApiKey $apiKey): string
    {
        if (! $apiKey->isEnabled()) {
            return self::ERROR_STRING_PATTERN;
        }

        return $apiKey->isExpired() ? self::WARNING_STRING_PATTERN : self::SUCCESS_STRING_PATTERN;
    }

    private function getEnabledSymbol(ApiKey $apiKey): string
    {
        return ! $apiKey->isEnabled() || $apiKey->isExpired() ? '---' : '+++';
    }
}
