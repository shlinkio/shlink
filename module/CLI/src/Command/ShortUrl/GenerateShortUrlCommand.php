<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\Core\Validation\ShortUrlMetaInputFilter;
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
use function method_exists;
use function sprintf;
use function strpos;

class GenerateShortUrlCommand extends Command
{
    public const NAME = 'short-url:generate';

    private UrlShortenerInterface $urlShortener;
    private array $domainConfig;
    private int $defaultShortCodeLength;

    public function __construct(UrlShortenerInterface $urlShortener, array $domainConfig, int $defaultShortCodeLength)
    {
        parent::__construct();
        $this->urlShortener = $urlShortener;
        $this->domainConfig = $domainConfig;
        $this->defaultShortCodeLength = $defaultShortCodeLength;
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
                'validSince',
                's',
                InputOption::VALUE_REQUIRED,
                'The date from which this short URL will be valid. '
                . 'If someone tries to access it before this date, it will not be found.',
            )
            ->addOption(
                'validUntil',
                'u',
                InputOption::VALUE_REQUIRED,
                'The date until which this short URL will be valid. '
                . 'If someone tries to access it after this date, it will not be found.',
            )
            ->addOption(
                'customSlug',
                'c',
                InputOption::VALUE_REQUIRED,
                'If provided, this slug will be used instead of generating a short code',
            )
            ->addOption(
                'maxVisits',
                'm',
                InputOption::VALUE_REQUIRED,
                'This will limit the number of visits for this short URL.',
            )
            ->addOption(
                'findIfExists',
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
                'shortCodeLength',
                'l',
                InputOption::VALUE_REQUIRED,
                'The length for generated short code (it will be ignored if --customSlug was provided).',
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
                'Forces the long URL to not be validated, regardless what is globally configured.',
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
        $shortCodeLength = $input->getOption('shortCodeLength') ?? $this->defaultShortCodeLength;
        $doValidateUrl = $this->doValidateUrl($input);

        try {
            $shortUrl = $this->urlShortener->shorten($longUrl, $tags, ShortUrlMeta::fromRawData([
                ShortUrlMetaInputFilter::VALID_SINCE => $input->getOption('validSince'),
                ShortUrlMetaInputFilter::VALID_UNTIL => $input->getOption('validUntil'),
                ShortUrlMetaInputFilter::CUSTOM_SLUG => $customSlug,
                ShortUrlMetaInputFilter::MAX_VISITS => $maxVisits !== null ? (int) $maxVisits : null,
                ShortUrlMetaInputFilter::FIND_IF_EXISTS => $input->getOption('findIfExists'),
                ShortUrlMetaInputFilter::DOMAIN => $input->getOption('domain'),
                ShortUrlMetaInputFilter::SHORT_CODE_LENGTH => $shortCodeLength,
                ShortUrlMetaInputFilter::VALIDATE_URL => $doValidateUrl,
            ]));

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

    private function doValidateUrl(InputInterface $input): ?bool
    {
        $rawInput = method_exists($input, '__toString') ? $input->__toString() : '';

        if (strpos($rawInput, '--no-validate-url') !== false) {
            return false;
        }
        if (strpos($rawInput, '--validate-url') !== false) {
            return true;
        }

        return null;
    }
}
