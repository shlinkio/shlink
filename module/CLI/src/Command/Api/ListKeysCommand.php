<?php
namespace Shlinkio\Shlink\CLI\Command\Api;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyService;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\I18n\Translator\TranslatorInterface;

class ListKeysCommand extends Command
{
    /**
     * @var ApiKeyServiceInterface
     */
    private $apiKeyService;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * ListKeysCommand constructor.
     * @param ApiKeyServiceInterface|ApiKeyService $apiKeyService
     * @param TranslatorInterface $translator
     *
     * @Inject({ApiKeyService::class, "translator"})
     */
    public function __construct(ApiKeyServiceInterface $apiKeyService, TranslatorInterface $translator)
    {
        $this->apiKeyService = $apiKeyService;
        $this->translator = $translator;
        parent::__construct(null);
    }

    public function configure()
    {
        $this->setName('api-key:list')
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
        if ($enabledOnly) {
            $table->setHeaders([
                $this->translator->translate('Key'),
                $this->translator->translate('Expiration date'),
            ]);
        } else {
            $table->setHeaders([
                $this->translator->translate('Key'),
                $this->translator->translate('Is enabled'),
                $this->translator->translate('Expiration date'),
            ]);
        }

        /** @var ApiKey $row */
        foreach ($list as $row) {
            $key = $row->getKey();
            $expiration = $row->getExpirationDate();
            $rowData = [];

            if ($enabledOnly) {
                $rowData[] = $key;
            } else {
                $rowData[] = $row->isEnabled() ? $this->getSuccessString($key) : $this->getErrorString($key);
                $rowData[] = $row->isEnabled() ? $this->getSuccessString('+++') : $this->getErrorString('---');
            }

            $rowData[] = isset($expiration) ? $expiration->format(\DateTime::ISO8601) : '-';
            $table->addRow($rowData);
        }

        $table->render();
    }

    /**
     * @param string $string
     * @return string
     */
    protected function getErrorString($string)
    {
        return sprintf('<fg=red>%s</>', $string);
    }

    /**
     * @param string $string
     * @return string
     */
    protected function getSuccessString($string)
    {
        return sprintf('<info>%s</info>', $string);
    }
}
