<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Spec;

use Happyr\DoctrineSpecification\Spec;
use Happyr\DoctrineSpecification\Specification\BaseSpecification;
use Happyr\DoctrineSpecification\Specification\Specification;
use Shlinkio\Shlink\Common\Util\DateRange;

class InDateRange extends BaseSpecification
{
    public function __construct(private ?DateRange $dateRange, private string $field = 'date')
    {
        parent::__construct();
    }

    protected function getSpec(): Specification
    {
        $criteria = [];

        if ($this->dateRange !== null && $this->dateRange->getStartDate() !== null) {
            $criteria[] = Spec::gte($this->field, $this->dateRange->getStartDate()->toDateTimeString());
        }

        if ($this->dateRange !== null && $this->dateRange->getEndDate() !== null) {
            $criteria[] = Spec::lte($this->field, $this->dateRange->getEndDate()->toDateTimeString());
        }

        return Spec::andX(...$criteria);
    }
}
