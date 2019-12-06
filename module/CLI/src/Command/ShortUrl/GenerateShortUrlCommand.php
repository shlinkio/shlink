<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zend\Diactoros\Uri;

use function array_map;
use function Functional\curry;
use function Functional\flatten;
use function Functional\unique;
use function sprintf;

class GenerateShortUrlCommand extends Command
{
    public const NAME = 'short-url:generate';
    private const ALIASES = ['shortcode:generate', 'short-code:generate'];

    /** @var UrlShortenerInterface */
    private $urlShortener;
    /** @var array */
    private $domainConfig;

    public function __construct(UrlShortenerInterface $urlShortener, array $domainConfig)
    {
        parent::__construct();
        $this->urlShortener = $urlShortener;
        $this->domainConfig = $domainConfig;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setAliases(self::ALIASES)
            ->setDescription('Generates a short URL for provided long URL and returns it')
            ->addArgument('longUrl', InputArgument::REQUIRED, 'The long URL to parse')
            ->addOption(
                'tags',
                't',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Tags to apply to the new short URL'
            )
            ->addOption(
                'validSince',
                's',
                InputOption::VALUE_REQUIRED,
                'The date from which this short URL will be valid. '
                . 'If someone tries to access it before this date, it will not be found.'
            )
            ->addOption(
                'validUntil',
                'u',
                InputOption::VALUE_REQUIRED,
                'The date until which this short URL will be valid. '
                . 'If someone tries to access it after this date, it will not be found.'
            )
            ->addOption(
                'customSlug',
                'c',
                InputOption::VALUE_REQUIRED,
                'If provided, this slug will be used instead of generating a short code'
            )
            ->addOption(
                'maxVisits',
                'm',
                InputOption::VALUE_REQUIRED,
                'This will limit the number of visits for this short URL.'
            )
            ->addOption(
                'findIfExists',
                'f',
                InputOption::VALUE_NONE,
                'This will force existing matching URL to be returned if found, instead of creating a new one.'
            )
            ->addOption(
                'domain',
                'd',
                InputOption::VALUE_REQUIRED,
                'The domain to which this short URL will be attached.'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        $longUrl = $input->getArgument('longUrl');
        if (! empty($longUrl)) {
            return;
        }

        $longUrl = $io->ask('Which URL do you want to shorten?');
        if (! empty($longUrl)) {
            $input->setArgument('longUrl', $longUrl);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);
        $longUrl = $input->getArgument('longUrl');
        if (empty($longUrl)) {
            $io->error('A URL was not provided!');
            return ExitCodes::EXIT_FAILURE;
        }

        $explodeWithComma = curry('explode')(',');
        $tags = unique(flatten(array_map($explodeWithComma, $input->getOption('tags'))));
        $customSlug = $input->getOption('customSlug');
        $maxVisits = $input->getOption('maxVisits');

        try {
            $shortUrl = $this->urlShortener->urlToShortCode(
                new Uri($longUrl),
                $tags,
                ShortUrlMeta::createFromParams(
                    $input->getOption('validSince'),
                    $input->getOption('validUntil'),
                    $customSlug,
                    $maxVisits !== null ? (int) $maxVisits : null,
                    $input->getOption('findIfExists'),
                    $input->getOption('domain')
                )
            );

            $io->writeln([
                sprintf('Processed long URL: <info>%s</info>', $longUrl),
                sprintf('Generated short URL: <info>%s</info>', $shortUrl->toString($this->domainConfig)),
            ]);
            return ExitCodes::EXIT_SUCCESS;
        } catch (InvalidUrlException | NonUniqueSlugException $e) {
            $io->error($e->getMessage());
            return ExitCodes::EXIT_FAILURE;
        }
    }
}
