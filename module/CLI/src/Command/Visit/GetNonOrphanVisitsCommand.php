<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Visit;

use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Symfony\Component\Console\Input\InputInterface;

class GetNonOrphanVisitsCommand extends AbstractVisitsListCommand
{
    public const NAME = 'visit:non-orphan';

    protected function doConfigure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Returns the list of non-orphan visits.');
    }

    protected function getVisitsPaginator(InputInterface $input, DateRange $dateRange): Paginator
    {
        return $this->visitsHelper->nonOrphanVisits(new VisitsParams($dateRange));
    }
}
