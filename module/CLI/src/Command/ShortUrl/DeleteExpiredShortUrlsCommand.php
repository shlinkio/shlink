<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\Core\ShortUrl\DeleteShortUrlServiceInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ExpiredShortUrlsConditions;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

#[AsCommand(
    name: DeleteExpiredShortUrlsCommand::NAME,
    description: 'Deletes all short URLs that are considered expired, because they have a validUntil date in the past',
)]
class DeleteExpiredShortUrlsCommand extends Command
{
    public const string NAME = 'short-url:delete-expired';

    public function __construct(private readonly DeleteShortUrlServiceInterface $deleteShortUrlService)
    {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        InputInterface $input,
        #[Option('Also take into consideration short URLs which have reached their max amount of visits.')]
        bool $evaluateMaxVisits = false,
        #[Option('Delete short URLs with no confirmation', shortcut: 'f')] bool $force = false,
        #[Option('Only check how many short URLs would be affected, without actually deleting them')]
        bool $dryRun = false,
    ): int {
        $conditions = new ExpiredShortUrlsConditions(maxVisitsReached: $evaluateMaxVisits);
        $force = $force || ! $input->isInteractive();

        if (! $force && ! $dryRun) {
            $io->warning([
                'Careful!',
                'You are about to perform a destructive operation that can result in deleted short URLs and visits.',
                'This action cannot be undone. Proceed at your own risk',
            ]);
            if (! $io->confirm('Continue?', default: false)) {
                return self::INVALID;
            }
        }

        if ($dryRun) {
            $result = $this->deleteShortUrlService->countExpiredShortUrls($conditions);
            $io->success(sprintf('There are %s expired short URLs matching provided conditions', $result));
            return self::SUCCESS;
        }

        $result = $this->deleteShortUrlService->deleteExpiredShortUrls($conditions);
        $io->success(sprintf('%s expired short URLs have been deleted', $result));

        return self::SUCCESS;
    }
}
