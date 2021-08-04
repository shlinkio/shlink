<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Spec;

use Happyr\DoctrineSpecification\Spec;
use Happyr\DoctrineSpecification\Specification\BaseSpecification;
use Happyr\DoctrineSpecification\Specification\Specification;
use Shlinkio\Shlink\Core\Spec\InDateRange;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;

class CountOfOrphanVisits extends BaseSpecification
{
    public function __construct(private VisitsCountFiltering $filtering)
    {
        parent::__construct();
    }

    protected function getSpec(): Specification
    {
        $conditions = [
            Spec::isNull('shortUrl'),
            new InDateRange($this->filtering->dateRange()),
        ];

        if ($this->filtering->excludeBots()) {
            $conditions[] = Spec::eq('potentialBot', false);
        }

        return Spec::countOf(Spec::andX(...$conditions));
    }
}
