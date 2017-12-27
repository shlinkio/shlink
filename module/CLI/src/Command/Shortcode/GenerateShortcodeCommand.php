<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Shortcode;

use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
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
    const NAME = 'shortcode:generate';

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
        $this->setName(self::NAME)
             ->setDescription(
                 $this->translator->translate('Generates a short code for provided URL and returns the short URL')
             )
             ->addArgument('longUrl', InputArgument::REQUIRED, $this->translator->translate('The long URL to parse'))
             ->addOption(
                 'tags',
                 't',
                 InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                 $this->translator->translate('Tags to apply to the new short URL')
             )
             ->addOption('validSince', 's', InputOption::VALUE_REQUIRED, $this->translator->translate(
                 'The date from which this short URL will be valid. '
                 . 'If someone tries to access it before this date, it will not be found.'
             ))
             ->addOption('validUntil', 'u', InputOption::VALUE_REQUIRED, $this->translator->translate(
                 'The date until which this short URL will be valid. '
                 . 'If someone tries to access it after this date, it will not be found.'
             ))
             ->addOption('customSlug', 'c', InputOption::VALUE_REQUIRED, $this->translator->translate(
                 'If provided, this slug will be used instead of generating a short code'
             ))
             ->addOption('maxVisits', 'm', InputOption::VALUE_REQUIRED, $this->translator->translate(
                 'This will limit the number of visits for this short URL.'
             ));
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
        $customSlug = $input->getOption('customSlug');
        $maxVisits = $input->getOption('maxVisits');

        try {
            if (! isset($longUrl)) {
                $output->writeln(sprintf('<error>%s</error>', $this->translator->translate('A URL was not provided!')));
                return;
            }

            $shortCode = $this->urlShortener->urlToShortCode(
                new Uri($longUrl),
                $tags,
                $this->getOptionalDate($input, 'validSince'),
                $this->getOptionalDate($input, 'validUntil'),
                $customSlug,
                $maxVisits !== null ? (int) $maxVisits : null
            );
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
        } catch (NonUniqueSlugException $e) {
            $output->writeln(sprintf(
                '<error>' . $this->translator->translate(
                    'Provided slug "%s" is already in use by another URL. Try with a different one.'
                ) . '</error>',
                $customSlug
            ));
        }
    }

    private function getOptionalDate(InputInterface $input, string $fieldName)
    {
        $since = $input->getOption($fieldName);
        return $since !== null ? new \DateTime($since) : null;
    }
}
