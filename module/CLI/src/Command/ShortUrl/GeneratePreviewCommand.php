<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\Common\Exception\PreviewGenerationException;
use Shlinkio\Shlink\Common\Service\PreviewGeneratorInterface;
use Shlinkio\Shlink\Core\Service\ShortUrlServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function sprintf;

class GeneratePreviewCommand extends Command
{
    public const NAME = 'short-url:process-previews';
    private const ALIASES = ['shortcode:process-previews', 'short-code:process-previews'];

    /** @var PreviewGeneratorInterface */
    private $previewGenerator;
    /** @var ShortUrlServiceInterface */
    private $shortUrlService;

    public function __construct(ShortUrlServiceInterface $shortUrlService, PreviewGeneratorInterface $previewGenerator)
    {
        parent::__construct();
        $this->shortUrlService = $shortUrlService;
        $this->previewGenerator = $previewGenerator;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setAliases(self::ALIASES)
            ->setDescription(
                'Processes and generates the previews for every URL, improving performance for later web requests.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $page = 1;
        do {
            $shortUrls = $this->shortUrlService->listShortUrls($page);
            $page += 1;

            foreach ($shortUrls as $shortUrl) {
                $this->processUrl($shortUrl->getLongUrl(), $output);
            }
        } while ($page <= $shortUrls->count());

        (new SymfonyStyle($input, $output))->success('Finished processing all URLs');
        return ExitCodes::EXIT_SUCCESS;
    }

    private function processUrl($url, OutputInterface $output): void
    {
        try {
            $output->write(sprintf('Processing URL %s...', $url));
            $this->previewGenerator->generatePreview($url);
            $output->writeln(' <info>Success!</info>');
        } catch (PreviewGenerationException $e) {
            $output->writeln(' <error>Error</error>');
            if ($output->isVerbose()) {
                $this->getApplication()->renderException($e, $output);
            }
        }
    }
}
