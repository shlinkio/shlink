<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Domain;

use Shlinkio\Shlink\CLI\Command\Visit\AbstractVisitsListCommand;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

class GetDomainVisitsCommand extends AbstractVisitsListCommand
{
    public const NAME = 'domain:visits';

    protected function doConfigure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Returns the list of visits for provided domain.')
            ->addArgument('domain', InputArgument::REQUIRED, 'The domain which visits we want to get.');
    }

    protected function getVisitsPaginator(InputInterface $input, DateRange $dateRange): Paginator
    {
        $domain = $input->getArgument('domain');
        return $this->visitsHelper->visitsForDomain($domain, new VisitsParams($dateRange));
    }
}
