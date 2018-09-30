<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Api;

use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zend\I18n\Translator\TranslatorInterface;
use function sprintf;

class DisableKeyCommand extends Command
{
    public const NAME = 'api-key:disable';

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

    protected function configure(): void
    {
        $this->setName(self::NAME)
             ->setDescription($this->translator->translate('Disables an API key.'))
             ->addArgument('apiKey', InputArgument::REQUIRED, $this->translator->translate('The API key to disable'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $apiKey = $input->getArgument('apiKey');
        $io = new SymfonyStyle($input, $output);

        try {
            $this->apiKeyService->disable($apiKey);
            $io->success(sprintf($this->translator->translate('API key "%s" properly disabled'), $apiKey));
        } catch (\InvalidArgumentException $e) {
            $io->error(sprintf($this->translator->translate('API key "%s" does not exist.'), $apiKey));
        }
    }
}
