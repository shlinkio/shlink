<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\Core\Util\ShortUrlBuilderTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zend\Diactoros\Uri;
use Zend\I18n\Translator\TranslatorInterface;

class GenerateShortUrlCommand extends Command
{
    use ShortUrlBuilderTrait;

    public const NAME = 'short-url:generate';
    private const ALIASES = ['shortcode:generate', 'short-code:generate'];

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

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setAliases(self::ALIASES)
            ->setDescription(
                $this->translator->translate('Generates a short URL for provided long URL and returns it')
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

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        $longUrl = $input->getArgument('longUrl');
        if (! empty($longUrl)) {
            return;
        }

        $longUrl = $io->ask(
            $this->translator->translate('A long URL was not provided. Which URL do you want to be shortened?')
        );
        if (! empty($longUrl)) {
            $input->setArgument('longUrl', $longUrl);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        $longUrl = $input->getArgument('longUrl');
        if (empty($longUrl)) {
            $io->error($this->translator->translate('A URL was not provided!'));
            return;
        }

        $tags = $input->getOption('tags');
        $processedTags = [];
        foreach ($tags as $key => $tag) {
            $explodedTags = \explode(',', $tag);
            $processedTags = \array_merge($processedTags, $explodedTags);
        }
        $tags = $processedTags;
        $customSlug = $input->getOption('customSlug');
        $maxVisits = $input->getOption('maxVisits');

        try {
            $shortCode = $this->urlShortener->urlToShortCode(
                new Uri($longUrl),
                $tags,
                $this->getOptionalDate($input, 'validSince'),
                $this->getOptionalDate($input, 'validUntil'),
                $customSlug,
                $maxVisits !== null ? (int) $maxVisits : null
            )->getShortCode();
            $shortUrl = $this->buildShortUrl($this->domainConfig, $shortCode);

            $io->writeln([
                \sprintf('%s <info>%s</info>', $this->translator->translate('Processed long URL:'), $longUrl),
                \sprintf('%s <info>%s</info>', $this->translator->translate('Generated short URL:'), $shortUrl),
            ]);
        } catch (InvalidUrlException $e) {
            $io->error(\sprintf(
                $this->translator->translate('Provided URL "%s" is invalid. Try with a different one.'),
                $longUrl
            ));
        } catch (NonUniqueSlugException $e) {
            $io->error(\sprintf(
                $this->translator->translate(
                    'Provided slug "%s" is already in use by another URL. Try with a different one.'
                ),
                $customSlug
            ));
        }
    }

    private function getOptionalDate(InputInterface $input, string $fieldName): ?\DateTime
    {
        $since = $input->getOption($fieldName);
        return $since !== null ? new \DateTime($since) : null;
    }
}
