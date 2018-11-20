<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Api;

use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function array_filter;
use function array_map;
use function sprintf;

class ListKeysCommand extends Command
{
    private const ERROR_STRING_PATTERN = '<fg=red>%s</>';
    private const SUCCESS_STRING_PATTERN = '<info>%s</info>';
    private const WARNING_STRING_PATTERN = '<comment>%s</comment>';

    public const NAME = 'api-key:list';

    /** @var ApiKeyServiceInterface */
    private $apiKeyService;

    public function __construct(ApiKeyServiceInterface $apiKeyService)
    {
        parent::__construct();
        $this->apiKeyService = $apiKeyService;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Lists all the available API keys.')
            ->addOption(
                'enabledOnly',
                'e',
                InputOption::VALUE_NONE,
                'Tells if only enabled API keys should be returned.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        $enabledOnly = $input->getOption('enabledOnly');

        $rows = array_map(function (ApiKey $apiKey) use ($enabledOnly) {
            $expiration = $apiKey->getExpirationDate();
            $messagePattern = $this->determineMessagePattern($apiKey);

            // Set columns for this row
            $rowData = [sprintf($messagePattern, $apiKey)];
            if (! $enabledOnly) {
                $rowData[] = sprintf($messagePattern, $this->getEnabledSymbol($apiKey));
            }
            $rowData[] = $expiration !== null ? $expiration->toAtomString() : '-';
            return $rowData;
        }, $this->apiKeyService->listKeys($enabledOnly));

        $io->table(array_filter([
            'Key',
            ! $enabledOnly ? 'Is enabled' : null,
            'Expiration date',
        ]), $rows);
    }

    private function determineMessagePattern(ApiKey $apiKey): string
    {
        if (! $apiKey->isEnabled()) {
            return self::ERROR_STRING_PATTERN;
        }

        return $apiKey->isExpired() ? self::WARNING_STRING_PATTERN : self::SUCCESS_STRING_PATTERN;
    }

    /**
     * @param ApiKey $apiKey
     * @return string
     */
    private function getEnabledSymbol(ApiKey $apiKey): string
    {
        return ! $apiKey->isEnabled() || $apiKey->isExpired() ? '---' : '+++';
    }
}
