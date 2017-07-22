<?php
namespace Shlinkio\Shlink\CLI\Command\Shortcode;

use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
             ->addArgument('longUrl', InputArgument::REQUIRED, $this->translator->translate('The long URL to parse'))
             ->addOption(
                 'tags',
                 't',
                 InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                 $this->translator->translate('Tags to apply to the new short URL')
             );
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
        $tags = $input->getOption('tags');
        $processedTags = [];
        foreach ($tags as $key => $tag) {
            $explodedTags = explode(',', $tag);
            $processedTags = array_merge($processedTags, $explodedTags);
        }
        $tags = $processedTags;

        try {
            if (! isset($longUrl)) {
                $output->writeln(sprintf('<error>%s</error>', $this->translator->translate('A URL was not provided!')));
                return;
            }

            $shortCode = $this->urlShortener->urlToShortCode(new Uri($longUrl), $tags);
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
