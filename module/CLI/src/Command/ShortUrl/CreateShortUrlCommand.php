<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\CLI\Command\BaseCommand;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;
use Shlinkio\Shlink\Core\Validation\ShortUrlInputFilter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_map;
use function Functional\curry;
use function Functional\flatten;
use function Functional\unique;
use function method_exists;
use function sprintf;
use function str_contains;

class CreateShortUrlCommand extends BaseCommand
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
            ->setAliases(['short-url:generate']) // Deprecated
            ->setDescription('Generates a short URL for provided long URL and returns it')
            ->addArgument('longUrl', InputArgument::REQUIRED, 'The long URL to parse')
            ->addOption(
                'tags',
                't',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Tags to apply to the new short URL',
            )
            ->addOptionWithDeprecatedFallback(
                'valid-since',
                's',
                InputOption::VALUE_REQUIRED,
                'The date from which this short URL will be valid. '
                . 'If someone tries to access it before this date, it will not be found.',
            )
            ->addOptionWithDeprecatedFallback(
                'valid-until',
                'u',
                InputOption::VALUE_REQUIRED,
                'The date until which this short URL will be valid. '
                . 'If someone tries to access it after this date, it will not be found.',
            )
            ->addOptionWithDeprecatedFallback(
                'custom-slug',
                'c',
                InputOption::VALUE_REQUIRED,
                'If provided, this slug will be used instead of generating a short code',
            )
            ->addOptionWithDeprecatedFallback(
                'max-visits',
                'm',
                InputOption::VALUE_REQUIRED,
                'This will limit the number of visits for this short URL.',
            )
            ->addOptionWithDeprecatedFallback(
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
            ->addOptionWithDeprecatedFallback(
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
                'no-validate-url',
                null,
                InputOption::VALUE_NONE,
                '[DEPRECATED] Forces the long URL to not be validated, regardless what is globally configured.',
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
        $customSlug = $this->getOptionWithDeprecatedFallback($input, 'custom-slug');
        $maxVisits = $this->getOptionWithDeprecatedFallback($input, 'max-visits');
        $shortCodeLength = $this->getOptionWithDeprecatedFallback(
            $input,
            'short-code-length',
        ) ?? $this->defaultShortCodeLength;
        $doValidateUrl = $this->doValidateUrl($input);

        try {
            $shortUrl = $this->urlShortener->shorten(ShortUrlMeta::fromRawData([
                ShortUrlInputFilter::LONG_URL => $longUrl,
                ShortUrlInputFilter::VALID_SINCE => $this->getOptionWithDeprecatedFallback($input, 'valid-since'),
                ShortUrlInputFilter::VALID_UNTIL => $this->getOptionWithDeprecatedFallback($input, 'valid-until'),
                ShortUrlInputFilter::CUSTOM_SLUG => $customSlug,
                ShortUrlInputFilter::MAX_VISITS => $maxVisits !== null ? (int) $maxVisits : null,
                ShortUrlInputFilter::FIND_IF_EXISTS => $this->getOptionWithDeprecatedFallback(
                    $input,
                    'find-if-exists',
                ),
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

    private function doValidateUrl(InputInterface $input): ?bool
    {
        $rawInput = method_exists($input, '__toString') ? $input->__toString() : '';

        if (str_contains($rawInput, '--no-validate-url')) {
            return false;
        }
        if (str_contains($rawInput, '--validate-url')) {
            return true;
        }

        return null;
    }

    private function getIO(InputInterface $input, OutputInterface $output): SymfonyStyle
    {
        return $this->io ?? ($this->io = new SymfonyStyle($input, $output));
    }
}
