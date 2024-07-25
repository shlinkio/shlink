<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\CLI\Input\ShortUrlDataInput;
use Shlinkio\Shlink\CLI\Util\ExitCode;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;
use Shlinkio\Shlink\Core\ShortUrl\UrlShortenerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

class CreateShortUrlCommand extends Command
{
    public const NAME = 'short-url:create';

    private ?SymfonyStyle $io;
    private readonly ShortUrlDataInput $shortUrlDataInput;

    public function __construct(
        private readonly UrlShortenerInterface $urlShortener,
        private readonly ShortUrlStringifierInterface $stringifier,
        private readonly UrlShortenerOptions $options,
    ) {
        parent::__construct();
        $this->shortUrlDataInput = new ShortUrlDataInput($this);
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Generates a short URL for provided long URL and returns it')
            ->addOption(
                'domain',
                'd',
                InputOption::VALUE_REQUIRED,
                'The domain to which this short URL will be attached.',
            )
            ->addOption(
                'custom-slug',
                'c',
                InputOption::VALUE_REQUIRED,
                'If provided, this slug will be used instead of generating a short code',
            )
            ->addOption(
                'short-code-length',
                'l',
                InputOption::VALUE_REQUIRED,
                'The length for generated short code (it will be ignored if --custom-slug was provided).',
            )
            ->addOption(
                'path-prefix',
                'p',
                InputOption::VALUE_REQUIRED,
                'Prefix to prepend before the generated short code or provided custom slug',
            )
            ->addOption(
                'find-if-exists',
                'f',
                InputOption::VALUE_NONE,
                'This will force existing matching URL to be returned if found, instead of creating a new one.',
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $this->verifyLongUrlArgument($input, $output);
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getIO($input, $output);
        $longUrl = $this->shortUrlDataInput->longUrl($input);
        if (empty($longUrl)) {
            $io->error('A URL was not provided!');
            return ExitCode::EXIT_FAILURE;
        }

        $shortCodeLength = $input->getOption('short-code-length') ?? $this->options->defaultShortCodesLength;

        try {
            $result = $this->urlShortener->shorten(ShortUrlCreation::fromRawData([
                ShortUrlInputFilter::LONG_URL => $longUrl,
                ShortUrlInputFilter::VALID_SINCE => $this->shortUrlDataInput->validSince($input),
                ShortUrlInputFilter::VALID_UNTIL => $this->shortUrlDataInput->validUntil($input),
                ShortUrlInputFilter::MAX_VISITS => $this->shortUrlDataInput->maxVisits($input),
                ShortUrlInputFilter::CUSTOM_SLUG => $input->getOption('custom-slug'),
                ShortUrlInputFilter::PATH_PREFIX => $input->getOption('path-prefix'),
                ShortUrlInputFilter::FIND_IF_EXISTS => $input->getOption('find-if-exists'),
                ShortUrlInputFilter::DOMAIN => $input->getOption('domain'),
                ShortUrlInputFilter::SHORT_CODE_LENGTH => $shortCodeLength,
                ShortUrlInputFilter::TAGS => $this->shortUrlDataInput->tags($input),
                ShortUrlInputFilter::CRAWLABLE => $this->shortUrlDataInput->crawlable($input),
                ShortUrlInputFilter::FORWARD_QUERY => !$this->shortUrlDataInput->noForwardQuery($input),
            ], $this->options));

            $result->onEventDispatchingError(static fn () => $io->isVerbose() && $io->warning(
                'Short URL properly created, but the real-time updates cannot be notified when generating the '
                . 'short URL from the command line. Migrate to roadrunner in order to bypass this limitation.',
            ));

            $io->writeln([
                sprintf('Processed long URL: <info>%s</info>', $longUrl),
                sprintf('Generated short URL: <info>%s</info>', $this->stringifier->stringify($result->shortUrl)),
            ]);
            return ExitCode::EXIT_SUCCESS;
        } catch (NonUniqueSlugException $e) {
            $io->error($e->getMessage());
            return ExitCode::EXIT_FAILURE;
        }
    }

    private function getIO(InputInterface $input, OutputInterface $output): SymfonyStyle
    {
        return $this->io ?? ($this->io = new SymfonyStyle($input, $output));
    }
}
