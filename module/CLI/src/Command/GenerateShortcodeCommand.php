<?php
namespace Shlinkio\Shlink\CLI\Command;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Zend\Diactoros\Uri;
use Zend\I18n\Translator\TranslatorInterface;

class GenerateShortcodeCommand extends Command
{
    /**
     * @var UrlShortenerInterface
     */
    private $urlShortener;
    /**
     * @var array
     */
    private $domainConfig;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * GenerateShortcodeCommand constructor.
     * @param UrlShortenerInterface|UrlShortener $urlShortener
     * @param TranslatorInterface $translator
     * @param array $domainConfig
     *
     * @Inject({UrlShortener::class, "translator", "config.url_shortener.domain"})
     */
    public function __construct(
        UrlShortenerInterface $urlShortener,
        TranslatorInterface $translator,
        array $domainConfig
    ) {
        $this->urlShortener = $urlShortener;
        $this->translator = $translator;
        $this->domainConfig = $domainConfig;
        parent::__construct(null);
    }

    public function configure()
    {
        $this->setName('shortcode:generate')
             ->setDescription(
                 $this->translator->translate('Generates a short code for provided URL and returns the short URL')
             )
             ->addArgument('longUrl', InputArgument::REQUIRED, $this->translator->translate('The long URL to parse'));
    }

    public function interact(InputInterface $input, OutputInterface $output)
    {
        $longUrl = $input->getArgument('longUrl');
        if (! empty($longUrl)) {
            return;
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new Question(sprintf(
            '<question>%s</question> ',
            $this->translator->translate('A long URL was not provided. Which URL do you want to shorten?:')
        ));

        $longUrl = $helper->ask($input, $output, $question);
        if (! empty($longUrl)) {
            $input->setArgument('longUrl', $longUrl);
        }
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $longUrl = $input->getArgument('longUrl');

        try {
            if (! isset($longUrl)) {
                $output->writeln(sprintf('<error>%s</error>', $this->translator->translate('A URL was not provided!')));
                return;
            }

            $shortCode = $this->urlShortener->urlToShortCode(new Uri($longUrl));
            $shortUrl = (new Uri())->withPath($shortCode)
                ->withScheme($this->domainConfig['schema'])
                ->withHost($this->domainConfig['hostname']);

            $output->writeln([
                sprintf('%s <info>%s</info>', $this->translator->translate('Processed URL:'), $longUrl),
                sprintf('%s <info>%s</info>', $this->translator->translate('Generated URL:'), $shortUrl),
            ]);
        } catch (InvalidUrlException $e) {
            $output->writeln(sprintf(
                '<error>' . $this->translator->translate(
                    'Provided URL "%s" is invalid. Try with a different one.'
                ) . '</error>',
                $longUrl
            ));
        }
    }
}
