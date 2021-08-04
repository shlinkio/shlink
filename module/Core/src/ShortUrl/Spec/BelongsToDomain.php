<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Spec;

use Happyr\DoctrineSpecification\Filter\Filter;
use Happyr\DoctrineSpecification\Spec;
use Happyr\DoctrineSpecification\Specification\BaseSpecification;

class BelongsToDomain extends BaseSpecification
{
    public function __construct(private string $domainId, private ?string $dqlAlias = null)
    {
        parent::__construct();
    }

    protected function getSpec(): Filter
    {
        return Spec::eq('domain', $this->domainId, $this->dqlAlias);
    }
}
