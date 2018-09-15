<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Shortcode;

use Shlinkio\Shlink\Core\Exception;
use Shlinkio\Shlink\Core\Service\ShortUrl\DeleteShortUrlServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zend\I18n\Translator\TranslatorInterface;

class DeleteShortCodeCommand extends Command
{
    public const NAME = 'short-code:delete';
    private const ALIASES = [];

    /**
     * @var DeleteShortUrlServiceInterface
     */
    private $deleteShortUrlService;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(DeleteShortUrlServiceInterface $deleteShortUrlService, TranslatorInterface $translator)
    {
        $this->deleteShortUrlService = $deleteShortUrlService;
        $this->translator = $translator;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setAliases(self::ALIASES)
            ->setDescription(
                $this->translator->translate('Deletes a short URL')
            )
            ->addArgument(
                'shortCode',
                InputArgument::REQUIRED,
                $this->translator->translate('The short code to be deleted')
            )
            ->addOption(
                'ignore-threshold',
                'i',
                InputOption::VALUE_NONE,
                $this->translator->translate(
                    'Ignores the safety visits threshold check, which could make short URLs with many visits to be '
                    . 'accidentally deleted'
                )
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        $shortCode = $input->getArgument('shortCode');
        $ignoreThreshold = $input->getOption('ignore-threshold');

        try {
            $this->runDelete($io, $shortCode, $ignoreThreshold);
        } catch (Exception\InvalidShortCodeException $e) {
            $io->error(
                \sprintf($this->translator->translate('Provided short code "%s" could not be found.'), $shortCode)
            );
        } catch (Exception\DeleteShortUrlException $e) {
            $this->retry($io, $shortCode, $e);
        }
    }

    private function retry(SymfonyStyle $io, string $shortCode, Exception\DeleteShortUrlException $e): void
    {
        $warningMsg = \sprintf($this->translator->translate(
            'It was not possible to delete the short URL with short code "%s" because it has more than %s visits.'
        ), $shortCode, $e->getVisitsThreshold());
        $io->writeln('<bg=yellow>' . $warningMsg . '</>');
        $forceDelete = $io->confirm($this->translator->translate('Do you want to delete it anyway?'), false);

        if ($forceDelete) {
            $this->runDelete($io, $shortCode, true);
        } else {
            $io->warning($this->translator->translate('Short URL was not deleted.'));
        }
    }

    private function runDelete(SymfonyStyle $io, string $shortCode, bool $ignoreThreshold): void
    {
        $this->deleteShortUrlService->deleteByShortCode($shortCode, $ignoreThreshold);
        $io->success(\sprintf(
            $this->translator->translate('Short URL with short code "%s" successfully deleted.'),
            $shortCode
        ));
    }
}
