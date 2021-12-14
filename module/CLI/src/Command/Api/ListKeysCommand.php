<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Api;

use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\CLI\Util\ShlinkTable;
use Shlinkio\Shlink\Rest\ApiKey\Role;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function array_filter;
use function Functional\map;
use function implode;
use function sprintf;

class ListKeysCommand extends Command
{
    private const ERROR_STRING_PATTERN = '<fg=red>%s</>';
    private const SUCCESS_STRING_PATTERN = '<info>%s</info>';
    private const WARNING_STRING_PATTERN = '<comment>%s</comment>';

    public const NAME = 'api-key:list';

    public function __construct(private ApiKeyServiceInterface $apiKeyService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Lists all the available API keys.')
            ->addOption(
                'enabled-only',
                'e',
                InputOption::VALUE_NONE,
                'Tells if only enabled API keys should be returned.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $enabledOnly = $input->getOption('enabled-only');

        $rows = map($this->apiKeyService->listKeys($enabledOnly), function (ApiKey $apiKey) use ($enabledOnly) {
            $expiration = $apiKey->getExpirationDate();
            $messagePattern = $this->determineMessagePattern($apiKey);

            // Set columns for this row
            $rowData = [sprintf($messagePattern, $apiKey), sprintf($messagePattern, $apiKey->name() ?? '-')];
            if (! $enabledOnly) {
                $rowData[] = sprintf($messagePattern, $this->getEnabledSymbol($apiKey));
            }
            $rowData[] = $expiration?->toAtomString() ?? '-';
            $rowData[] = $apiKey->isAdmin() ? 'Admin' : implode("\n", $apiKey->mapRoles(
                fn (string $roleName, array $meta) =>
                    empty($meta)
                        ? Role::toFriendlyName($roleName)
                        : sprintf('%s: %s', Role::toFriendlyName($roleName), Role::domainAuthorityFromMeta($meta)),
            ));

            return $rowData;
        });

        ShlinkTable::withRowSeparators($output)->render(array_filter([
            'Key',
            'Name',
            ! $enabledOnly ? 'Is enabled' : null,
            'Expiration date',
            'Roles',
        ]), $rows);

        return ExitCodes::EXIT_SUCCESS;
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
