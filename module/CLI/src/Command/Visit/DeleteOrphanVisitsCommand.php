<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Visit;

use Shlinkio\Shlink\CLI\Util\ExitCode;
use Shlinkio\Shlink\Core\Visit\VisitsDeleterInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

class DeleteOrphanVisitsCommand extends AbstractDeleteVisitsCommand
{
    public const string NAME = 'visit:orphan-delete';

    public function __construct(private readonly VisitsDeleterInterface $deleter)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Deletes all orphan visits');
    }

    protected function doExecute(InputInterface $input, SymfonyStyle $io): int
    {
        $result = $this->deleter->deleteOrphanVisits();
        $io->success(sprintf('Successfully deleted %s visits', $result->affectedItems));

        return ExitCode::EXIT_SUCCESS;
    }

    protected function getWarningMessage(): string
    {
        return 'You are about to delete all orphan visits. This operation cannot be undone.';
    }
}
