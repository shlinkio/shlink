<?php
namespace Shlinkio\Shlink\CLI\Command\Api;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Shlinkio\Shlink\Rest\Service\ApiKeyService;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\I18n\Translator\TranslatorInterface;

class GenerateKeyCommand extends Command
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
     * GenerateKeyCommand constructor.
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
        $this->setName('api-key:generate')
             ->setDescription($this->translator->translate('Generates a new valid API key.'))
             ->addOption(
                 'expirationDate',
                 'e',
                 InputOption::VALUE_OPTIONAL,
                 $this->translator->translate('The date in which the API key should expire. Use any valid PHP format.')
             );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $expirationDate = $input->getOption('expirationDate');
        $apiKey = $this->apiKeyService->create(isset($expirationDate) ? new \DateTime($expirationDate) : null);
        $output->writeln($this->translator->translate('Generated API key') . sprintf(': <info>%s</info>', $apiKey));
    }
}
