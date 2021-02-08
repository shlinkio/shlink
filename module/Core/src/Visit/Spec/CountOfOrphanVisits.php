<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Spec;

use Happyr\DoctrineSpecification\BaseSpecification;
use Happyr\DoctrineSpecification\Spec;
use Happyr\DoctrineSpecification\Specification\Specification;

class CountOfOrphanVisits extends BaseSpecification
{
    protected function getSpec(): Specification
    {
        return Spec::countOf(Spec::isNull('shortUrl'));
    }
}
