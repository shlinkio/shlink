<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\CLI\Input\ShortUrlIdentifierInput;
use Shlinkio\Shlink\CLI\Util\ExitCode;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolverInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

class ResolveUrlCommand extends Command
{
    public const NAME = 'short-url:parse';

    private readonly ShortUrlIdentifierInput $shortUrlIdentifierInput;

    public function __construct(private readonly ShortUrlResolverInterface $urlResolver)
    {
        parent::__construct();
        $this->shortUrlIdentifierInput = new ShortUrlIdentifierInput(
            $this,
            shortCodeDesc: 'The short code to parse',
            domainDesc: 'The domain to which the short URL is attached.',
        );
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Returns the long URL behind a short code');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $shortCode = $this->shortUrlIdentifierInput->shortCode($input);
        if (! empty($shortCode)) {
            return;
        }

        $io = new SymfonyStyle($input, $output);
        $shortCode = $io->ask('A short code was not provided. Which short code do you want to parse?');
        if (! empty($shortCode)) {
            $input->setArgument('shortCode', $shortCode);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $url = $this->urlResolver->resolveShortUrl($this->shortUrlIdentifierInput->toShortUrlIdentifier($input));
            $output->writeln(sprintf('Long URL: <info>%s</info>', $url->getLongUrl()));
            return ExitCode::EXIT_SUCCESS;
        } catch (ShortUrlNotFoundException $e) {
            $io->error($e->getMessage());
            return ExitCode::EXIT_FAILURE;
        }
    }
}
