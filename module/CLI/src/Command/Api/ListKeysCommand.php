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
use Zend\I18n\Translator\TranslatorInterface;

class ListKeysCommand extends Command
{
    private const ERROR_STRING_PATTERN = '<fg=red>%s</>';
    private const SUCCESS_STRING_PATTERN = '<info>%s</info>';
    private const WARNING_STRING_PATTERN = '<comment>%s</comment>';

    public const NAME = 'api-key:list';

    /**
     * @var ApiKeyServiceInterface
     */
    private $apiKeyService;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(ApiKeyServiceInterface $apiKeyService, TranslatorInterface $translator)
    {
        $this->apiKeyService = $apiKeyService;
        $this->translator = $translator;
        parent::__construct();
    }

    public function configure()
    {
        $this->setName(self::NAME)
             ->setDescription($this->translator->translate('Lists all the available API keys.'))
             ->addOption(
                 'enabledOnly',
                 null,
                 InputOption::VALUE_NONE,
                 $this->translator->translate('Tells if only enabled API keys should be returned.')
             );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $enabledOnly = $input->getOption('enabledOnly');
        $list = $this->apiKeyService->listKeys($enabledOnly);
        $rows = [];

        /** @var ApiKey $row */
        foreach ($list as $row) {
            $key = $row->getKey();
            $expiration = $row->getExpirationDate();
            $messagePattern = $this->determineMessagePattern($row);

            // Set columns for this row
            $rowData = [\sprintf($messagePattern, $key)];
            if (! $enabledOnly) {
                $rowData[] = \sprintf($messagePattern, $this->getEnabledSymbol($row));
            }
            $rowData[] = $expiration !== null ? $expiration->toAtomString() : '-';

            $rows[] = $rowData;
        }

        $io->table(\array_filter([
            $this->translator->translate('Key'),
            ! $enabledOnly ? $this->translator->translate('Is enabled') : null,
            $this->translator->translate('Expiration date'),
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
