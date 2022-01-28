<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;
use Shlinkio\Shlink\Core\Validation\ShortUrlInputFilter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_map;
use function Functional\curry;
use function Functional\flatten;
use function Functional\unique;
use function sprintf;

class CreateShortUrlCommand extends Command
{
    public const NAME = 'short-url:create';

    private ?SymfonyStyle $io;

    public function __construct(
        private UrlShortenerInterface $urlShortener,
        private ShortUrlStringifierInterface $stringifier,
        private int $defaultShortCodeLength,
        private string $defaultDomain,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Generates a short URL for provided long URL and returns it')
            ->addArgument('longUrl', InputArgument::REQUIRED, 'The long URL to parse')
            ->addOption(
                'tags',
                't',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Tags to apply to the new short URL',
            )
            ->addOption(
                'valid-since',
                's',
                InputOption::VALUE_REQUIRED,
                'The date from which this short URL will be valid. '
                . 'If someone tries to access it before this date, it will not be found.',
            )
            ->addOption(
                'valid-until',
                'u',
                InputOption::VALUE_REQUIRED,
                'The date until which this short URL will be valid. '
                . 'If someone tries to access it after this date, it will not be found.',
            )
            ->addOption(
                'custom-slug',
                'c',
                InputOption::VALUE_REQUIRED,
                'If provided, this slug will be used instead of generating a short code',
            )
            ->addOption(
                'max-visits',
                'm',
                InputOption::VALUE_REQUIRED,
                'This will limit the number of visits for this short URL.',
            )
            ->addOption(
                'find-if-exists',
                'f',
                InputOption::VALUE_NONE,
                'This will force existing matching URL to be returned if found, instead of creating a new one.',
            )
            ->addOption(
                'domain',
                'd',
                InputOption::VALUE_REQUIRED,
                'The domain to which this short URL will be attached.',
            )
            ->addOption(
                'short-code-length',
                'l',
                InputOption::VALUE_REQUIRED,
                'The length for generated short code (it will be ignored if --custom-slug was provided).',
            )
            ->addOption(
                'validate-url',
                null,
                InputOption::VALUE_NONE,
                'Forces the long URL to be validated, regardless what is globally configured.',
            )
            ->addOption(
                'crawlable',
                'r',
                InputOption::VALUE_NONE,
                'Tells if this URL will be included as "Allow" in Shlink\'s robots.txt.',
            )
            ->addOption(
                'no-forward-query',
                'w',
                InputOption::VALUE_NONE,
                'Disables the forwarding of the query string to the long URL, when the new short URL is visited.',
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $this->verifyLongUrlArgument($input, $output);
        $this->verifyDomainArgument($input);
    }

    private function verifyLongUrlArgument(InputInterface $input, OutputInterface $output): void
    {
        $longUrl = $input->getArgument('longUrl');
        if (! empty($longUrl)) {
            return;
        }

        $io = $this->getIO($input, $output);
        $longUrl = $io->ask('Which URL do you want to shorten?');
        if (! empty($longUrl)) {
            $input->setArgument('longUrl', $longUrl);
        }
    }

    private function verifyDomainArgument(InputInterface $input): void
    {
        $domain = $input->getOption('domain');
        $input->setOption('domain', $domain === $this->defaultDomain ? null : $domain);
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = $this->getIO($input, $output);
        $longUrl = $input->getArgument('longUrl');
        if (empty($longUrl)) {
            $io->error('A URL was not provided!');
            return ExitCodes::EXIT_FAILURE;
        }

        $explodeWithComma = curry('explode')(',');
        $tags = unique(flatten(array_map($explodeWithComma, $input->getOption('tags'))));
        $customSlug = $input->getOption('custom-slug');
        $maxVisits = $input->getOption('max-visits');
        $shortCodeLength = $input->getOption('short-code-length') ?? $this->defaultShortCodeLength;
        $doValidateUrl = $input->getOption('validate-url');

        try {
            $shortUrl = $this->urlShortener->shorten(ShortUrlMeta::fromRawData([
                ShortUrlInputFilter::LONG_URL => $longUrl,
                ShortUrlInputFilter::VALID_SINCE => $input->getOption('valid-since'),
                ShortUrlInputFilter::VALID_UNTIL => $input->getOption('valid-until'),
                ShortUrlInputFilter::CUSTOM_SLUG => $customSlug,
                ShortUrlInputFilter::MAX_VISITS => $maxVisits !== null ? (int) $maxVisits : null,
                ShortUrlInputFilter::FIND_IF_EXISTS => $input->getOption('find-if-exists'),
                ShortUrlInputFilter::DOMAIN => $input->getOption('domain'),
                ShortUrlInputFilter::SHORT_CODE_LENGTH => $shortCodeLength,
                ShortUrlInputFilter::VALIDATE_URL => $doValidateUrl,
                ShortUrlInputFilter::TAGS => $tags,
                ShortUrlInputFilter::CRAWLABLE => $input->getOption('crawlable'),
                ShortUrlInputFilter::FORWARD_QUERY => !$input->getOption('no-forward-query'),
            ]));

            $io->writeln([
                sprintf('Processed long URL: <info>%s</info>', $longUrl),
                sprintf('Generated short URL: <info>%s</info>', $this->stringifier->stringify($shortUrl)),
            ]);
            return ExitCodes::EXIT_SUCCESS;
        } catch (InvalidUrlException | NonUniqueSlugException $e) {
            $io->error($e->getMessage());
            return ExitCodes::EXIT_FAILURE;
        }
    }

    private function getIO(InputInterface $input, OutputInterface $output): SymfonyStyle
    {
        return $this->io ?? ($this->io = new SymfonyStyle($input, $output));
    }
}
