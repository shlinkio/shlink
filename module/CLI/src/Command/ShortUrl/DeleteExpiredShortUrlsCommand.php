<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\CLI\Util\ExitCode;
use Shlinkio\Shlink\Core\ShortUrl\DeleteShortUrlServiceInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ExpiredShortUrlsConditions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

class DeleteExpiredShortUrlsCommand extends Command
{
    public const NAME = 'short-url:delete-expired';

    public function __construct(private readonly DeleteShortUrlServiceInterface $deleteShortUrlService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription(
                'Deletes all short URLs that are considered expired, because they have a validUntil date in the past',
            )
            ->addOption(
                'evaluate-max-visits',
                mode: InputOption::VALUE_NONE,
                description: 'Also take into consideration short URLs which have reached their max amount of visits.',
            )
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Delete short URLs with no confirmation')
            ->addOption(
                'dry-run',
                mode: InputOption::VALUE_NONE,
                description: 'Delete short URLs with no confirmation',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force') || ! $input->isInteractive();
        $dryRun = $input->getOption('dry-run');
        $conditions = new ExpiredShortUrlsConditions(maxVisitsReached: $input->getOption('evaluate-max-visits'));

        if (! $force && ! $dryRun) {
            $io->warning([
                'Careful!',
                'You are about to perform a destructive operation that can result in deleted short URLs and visits.',
                'This action cannot be undone. Proceed at your own risk',
            ]);
            if (! $io->confirm('Continue?', default: false)) {
                return ExitCode::EXIT_WARNING;
            }
        }

        if ($dryRun) {
            $result = $this->deleteShortUrlService->countExpiredShortUrls($conditions);
            $io->success(sprintf('There are %s expired short URLs matching provided conditions', $result));
            return ExitCode::EXIT_SUCCESS;
        }

        $result = $this->deleteShortUrlService->deleteExpiredShortUrls($conditions);
        $io->success(sprintf('%s expired short URLs have been deleted', $result));
        return ExitCode::EXIT_SUCCESS;
    }
}
