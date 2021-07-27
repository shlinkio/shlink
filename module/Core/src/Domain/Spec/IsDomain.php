<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Domain\Spec;

use Happyr\DoctrineSpecification\Filter\Filter;
use Happyr\DoctrineSpecification\Spec;
use Happyr\DoctrineSpecification\Specification\BaseSpecification;

class IsDomain extends BaseSpecification
{
    public function __construct(private string $domainId, ?string $context = null)
    {
        parent::__construct($context);
    }

    protected function getSpec(): Filter
    {
        return Spec::eq('id', $this->domainId);
    }
}
