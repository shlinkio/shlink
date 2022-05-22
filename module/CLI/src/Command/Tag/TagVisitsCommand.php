<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Tag;

use Shlinkio\Shlink\CLI\Command\Visit\AbstractVisitsListCommand;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

class TagVisitsCommand extends AbstractVisitsListCommand
{
    public const NAME = 'tag:visits';

    protected function doConfigure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Returns the list of visits for provided tag.')
            ->addArgument('tag', InputArgument::REQUIRED, 'The tag which visits we want to get.');
    }

    protected function getVisitsPaginator(InputInterface $input, DateRange $dateRange): Paginator
    {
        $tag = $input->getArgument('tag');
        return $this->visitsHelper->visitsForTag($tag, new VisitsParams($dateRange));
    }
}
