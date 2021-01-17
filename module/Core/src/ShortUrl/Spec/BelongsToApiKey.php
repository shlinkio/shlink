<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Spec;

use Happyr\DoctrineSpecification\BaseSpecification;
use Happyr\DoctrineSpecification\Filter\Filter;
use Happyr\DoctrineSpecification\Spec;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class BelongsToApiKey extends BaseSpecification
{
    private ApiKey $apiKey;
    private string $dqlAlias;

    public function __construct(ApiKey $apiKey, ?string $dqlAlias = null)
    {
        $this->apiKey = $apiKey;
        $this->dqlAlias = $dqlAlias ?? 's';
        parent::__construct($this->dqlAlias);
    }

    protected function getSpec(): Filter
    {
        return Spec::eq('authorApiKey', $this->apiKey, $this->dqlAlias);
    }
}
