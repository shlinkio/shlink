<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Visit;

use Shlinkio\Shlink\CLI\Input\DomainOption;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\OrphanVisitsParams;
use Shlinkio\Shlink\Core\Visit\Model\OrphanVisitType;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

use function Shlinkio\Shlink\Core\enumToString;
use function sprintf;

class GetOrphanVisitsCommand extends AbstractVisitsListCommand
{
    public const string NAME = 'visit:orphan';

    private readonly DomainOption $domainOption;

    public function __construct(VisitsStatsHelperInterface $visitsHelper)
    {
        parent::__construct($visitsHelper);
        $this->domainOption = new DomainOption($this, sprintf(
            'Return visits that belong to this domain only. Use %s keyword for visits in default domain',
            Domain::DEFAULT_AUTHORITY,
        ));
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Returns the list of orphan visits.')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, sprintf(
                'Return visits only with this type. One of %s',
                enumToString(OrphanVisitType::class),
            ));
    }

    /**
     * @return Paginator<Visit>
     */
    protected function getVisitsPaginator(InputInterface $input, DateRange $dateRange): Paginator
    {
        $rawType = $input->getOption('type');
        $type = $rawType !== null ? OrphanVisitType::from($rawType) : null;
        return $this->visitsHelper->orphanVisits(new OrphanVisitsParams(
            dateRange: $dateRange,
            domain: $this->domainOption->get($input),
            type: $type,
        ));
    }

    /**
     * @return array<string, string>
     */
    protected function mapExtraFields(Visit $visit): array
    {
        return ['type' => $visit->type->value];
    }
}
