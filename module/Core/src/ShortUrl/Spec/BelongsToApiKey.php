<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Spec;

use Happyr\DoctrineSpecification\Filter\Filter;
use Happyr\DoctrineSpecification\Spec;
use Happyr\DoctrineSpecification\Specification\BaseSpecification;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class BelongsToApiKey extends BaseSpecification
{
    public function __construct(private ApiKey $apiKey, private ?string $dqlAlias = null)
    {
        parent::__construct();
    }

    protected function getSpec(): Filter
    {
        return Spec::eq('authorApiKey', $this->apiKey, $this->dqlAlias);
    }
}
