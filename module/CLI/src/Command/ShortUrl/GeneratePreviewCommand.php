<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\Common\Exception\PreviewGenerationException;
use Shlinkio\Shlink\Common\Service\PreviewGeneratorInterface;
use Shlinkio\Shlink\Core\Service\ShortUrlServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zend\I18n\Translator\TranslatorInterface;
use function sprintf;

class GeneratePreviewCommand extends Command
{
    public const NAME = 'short-url:process-previews';
    private const ALIASES = ['shortcode:process-previews', 'short-code:process-previews'];

    /**
     * @var PreviewGeneratorInterface
     */
    private $previewGenerator;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var ShortUrlServiceInterface
     */
    private $shortUrlService;

    public function __construct(
        ShortUrlServiceInterface $shortUrlService,
        PreviewGeneratorInterface $previewGenerator,
        TranslatorInterface $translator
    ) {
        $this->shortUrlService = $shortUrlService;
        $this->previewGenerator = $previewGenerator;
        $this->translator = $translator;
        parent::__construct(null);
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setAliases(self::ALIASES)
            ->setDescription(
                $this->translator->translate(
                    'Processes and generates the previews for every URL, improving performance for later web requests.'
                )
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $page = 1;
        do {
            $shortUrls = $this->shortUrlService->listShortUrls($page);
            $page += 1;

            foreach ($shortUrls as $shortUrl) {
                $this->processUrl($shortUrl->getLongUrl(), $output);
            }
        } while ($page <= $shortUrls->count());

        (new SymfonyStyle($input, $output))->success($this->translator->translate('Finished processing all URLs'));
    }

    private function processUrl($url, OutputInterface $output): void
    {
        try {
            $output->write(sprintf($this->translator->translate('Processing URL %s...'), $url));
            $this->previewGenerator->generatePreview($url);
            $output->writeln($this->translator->translate(' <info>Success!</info>'));
        } catch (PreviewGenerationException $e) {
            $output->writeln(' <error>' . $this->translator->translate('Error') . '</error>');
            if ($output->isVerbose()) {
                $this->getApplication()->renderException($e, $output);
            }
        }
    }
}
