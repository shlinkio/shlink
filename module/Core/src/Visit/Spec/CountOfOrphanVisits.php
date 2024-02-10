<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Spec;

use Happyr\DoctrineSpecification\Spec;
use Happyr\DoctrineSpecification\Specification\BaseSpecification;
use Happyr\DoctrineSpecification\Specification\Specification;
use Shlinkio\Shlink\Core\Spec\InDateRange;
use Shlinkio\Shlink\Core\Visit\Persistence\OrphanVisitsCountFiltering;

class CountOfOrphanVisits extends BaseSpecification
{
    public function __construct(private readonly OrphanVisitsCountFiltering $filtering)
    {
        parent::__construct();
    }

    protected function getSpec(): Specification
    {
        $conditions = [
            Spec::isNull('shortUrl'),
            new InDateRange($this->filtering->dateRange),
        ];

        if ($this->filtering->excludeBots) {
            $conditions[] = Spec::eq('potentialBot', false);
        }

        if ($this->filtering->type) {
            $conditions[] = Spec::eq('type', $this->filtering->type->value);
        }

        return Spec::countOf(Spec::andX(...$conditions));
    }
}
