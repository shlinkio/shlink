<?php
namespace Shlinkio\Shlink\CLI\Command\Shortcode;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Shlinkio\Shlink\Common\Exception\PreviewGenerationException;
use Shlinkio\Shlink\Common\Service\PreviewGenerator;
use Shlinkio\Shlink\Common\Service\PreviewGeneratorInterface;
use Shlinkio\Shlink\Core\Service\ShortUrlService;
use Shlinkio\Shlink\Core\Service\ShortUrlServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\I18n\Translator\TranslatorInterface;

class GeneratePreviewCommand extends Command
{
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

    /**
     * GeneratePreviewCommand constructor.
     * @param ShortUrlServiceInterface $shortUrlService
     * @param PreviewGeneratorInterface $previewGenerator
     * @param TranslatorInterface $translator
     *
     * @Inject({ShortUrlService::class, PreviewGenerator::class, "translator"})
     */
    public function __construct(
        ShortUrlServiceInterface $shortUrlService,
        PreviewGeneratorInterface $previewGenerator,
        TranslatorInterface $translator
    ) {
        $this->previewGenerator = $previewGenerator;
        $this->translator = $translator;
        $this->shortUrlService = $shortUrlService;
        parent::__construct(null);
    }

    public function configure()
    {
        $this->setName('shortcode:process-previews')
             ->setDescription(
                 $this->translator->translate(
                     'Processes and generates the previews for every URL, improving performance for later web requests.'
                 )
             );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $page = 1;
        do {
            $shortUrls = $this->shortUrlService->listShortUrls($page);
            $page += 1;

            foreach ($shortUrls as $shortUrl) {
                $this->processUrl($shortUrl->getOriginalUrl(), $output);
            }
        } while ($page <= $shortUrls->count());

        $output->writeln('<info>' . $this->translator->translate('Finished processing all URLs') . '</info>');
    }

    protected function processUrl($url, OutputInterface $output)
    {
        try {
            $output->write(sprintf($this->translator->translate('Processing URL %s...'), $url));
            $this->previewGenerator->generatePreview($url);
            $output->writeln($this->translator->translate(' <info>Success!</info>'));
        } catch (PreviewGenerationException $e) {
            $messages = [' <error>' . $this->translator->translate('Error') . '</error>'];
            if ($output->isVerbose()) {
                $messages[] = '<error>' . $e->__toString() . '</error>';
            }

            $output->writeln($messages);
        }
    }
}
