<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Api;

use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\I18n\Translator\TranslatorInterface;

class ListKeysCommand extends Command
{
    const NAME = 'api-key:list';

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
        $enabledOnly = $input->getOption('enabledOnly');
        $list = $this->apiKeyService->listKeys($enabledOnly);

        $table = new Table($output);
        $table->setHeaders(array_filter([
            $this->translator->translate('Key'),
            ! $enabledOnly ? $this->translator->translate('Is enabled') : null,
            $this->translator->translate('Expiration date'),
        ]));

        /** @var ApiKey $row */
        foreach ($list as $row) {
            $key = $row->getKey();
            $expiration = $row->getExpirationDate();
            $rowData = [];
            $formatMethod = $this->determineFormatMethod($row);

            $rowData[] = $formatMethod($key);
            if (! $enabledOnly) {
                $rowData[] = $formatMethod($this->getEnabledSymbol($row));
            }

            $rowData[] = $expiration !== null ? $expiration->format(\DateTime::ATOM) : '-';
            $table->addRow($rowData);
        }

        $table->render();
    }

    private function determineFormatMethod(ApiKey $apiKey): callable
    {
        if (! $apiKey->isEnabled()) {
            return [$this, 'getErrorString'];
        }

        return $apiKey->isExpired() ? [$this, 'getWarningString'] : [$this, 'getSuccessString'];
    }

    /**
     * @param string $value
     * @return string
     */
    private function getErrorString(string $value): string
    {
        return sprintf('<fg=red>%s</>', $value);
    }

    /**
     * @param string $value
     * @return string
     */
    private function getSuccessString(string $value): string
    {
        return sprintf('<info>%s</info>', $value);
    }

    /**
     * @param string $value
     * @return string
     */
    private function getWarningString(string $value): string
    {
        return sprintf('<comment>%s</comment>', $value);
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
