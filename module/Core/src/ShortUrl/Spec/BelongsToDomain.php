<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Spec;

use Happyr\DoctrineSpecification\Filter\Filter;
use Happyr\DoctrineSpecification\Spec;
use Happyr\DoctrineSpecification\Specification\BaseSpecification;

class BelongsToDomain extends BaseSpecification
{
    private string $domainId;
    private ?string $dqlAlias;

    public function __construct(string $domainId, ?string $dqlAlias = null)
    {
        $this->domainId = $domainId;
        $this->dqlAlias = $dqlAlias;
        parent::__construct();
    }

    protected function getSpec(): Filter
    {
        return Spec::eq('domain', $this->domainId, $this->dqlAlias);
    }
}
