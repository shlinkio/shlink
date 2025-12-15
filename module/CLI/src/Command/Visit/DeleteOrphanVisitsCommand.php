<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Visit;

use Shlinkio\Shlink\CLI\Command\Util\CommandUtils;
use Shlinkio\Shlink\Core\Visit\VisitsDeleterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

#[AsCommand(DeleteOrphanVisitsCommand::NAME, 'Deletes all orphan visits')]
class DeleteOrphanVisitsCommand extends Command
{
    public const string NAME = 'visit:orphan-delete';

    public function __construct(private readonly VisitsDeleterInterface $deleter)
    {
        parent::__construct();
    }

    public function __invoke(SymfonyStyle $io): int
    {
        return CommandUtils::executeWithWarning(
            'You are about to delete all orphan visits. This operation cannot be undone',
            $io,
            fn () => $this->deleteVisits($io),
        );
    }

    private function deleteVisits(SymfonyStyle $io): int
    {
        $result = $this->deleter->deleteOrphanVisits();
        $io->success(sprintf('Successfully deleted %s visits', $result->affectedItems));

        return self::SUCCESS;
    }
}
