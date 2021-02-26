<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Spec;

use Happyr\DoctrineSpecification\Spec;
use Happyr\DoctrineSpecification\Specification\BaseSpecification;
use Happyr\DoctrineSpecification\Specification\Specification;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Spec\InDateRange;

class CountOfOrphanVisits extends BaseSpecification
{
    private ?DateRange $dateRange;

    public function __construct(?DateRange $dateRange)
    {
        parent::__construct();
        $this->dateRange = $dateRange;
    }

    protected function getSpec(): Specification
    {
        return Spec::countOf(Spec::andX(
            Spec::isNull('shortUrl'),
            new InDateRange($this->dateRange),
        ));
    }
}
