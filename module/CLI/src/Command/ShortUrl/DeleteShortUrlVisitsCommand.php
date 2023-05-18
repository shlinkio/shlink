<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\CLI\Util\ExitCode;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlVisitsDeleterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

class DeleteShortUrlVisitsCommand extends Command
{
    public const NAME = 'short-url:visits-delete';

    public function __construct(private readonly ShortUrlVisitsDeleterInterface $deleter)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Deletes visits from a short URL')
            ->addArgument(
                'shortCode',
                InputArgument::REQUIRED,
                'The short code for the short URL which visits will be deleted',
            )
            ->addOption(
                'domain',
                'd',
                InputOption::VALUE_REQUIRED,
                'The domain if the short code does not belong to the default one',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $identifier = ShortUrlIdentifier::fromCli($input);
        $io = new SymfonyStyle($input, $output);
        if (! $this->confirm($io)) {
            $io->info('Operation aborted');
            return ExitCode::EXIT_SUCCESS;
        }

        try {
            $result = $this->deleter->deleteShortUrlVisits($identifier);
            $io->success(sprintf('Successfully deleted %s visits', $result->affectedItems));

            return ExitCode::EXIT_SUCCESS;
        } catch (ShortUrlNotFoundException) {
            $io->warning(sprintf('Short URL not found for "%s"', $identifier->__toString()));
            return ExitCode::EXIT_WARNING;
        }
    }

    private function confirm(SymfonyStyle $io): bool
    {
        $io->warning('You are about to delete all visits for a short URL. This operation cannot be undone.');
        return $io->confirm('<comment>Continue deleting visits?</comment>', false);
    }
}
