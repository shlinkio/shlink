<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Spec;

use Happyr\DoctrineSpecification\Spec;
use Happyr\DoctrineSpecification\Specification\BaseSpecification;
use Happyr\DoctrineSpecification\Specification\Specification;
use Shlinkio\Shlink\Rest\ApiKey\Spec\WithApiKeySpecsEnsuringJoin;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class CountOfShortUrlVisits extends BaseSpecification
{
    private ?ApiKey $apiKey;

    public function __construct(?ApiKey $apiKey)
    {
        parent::__construct();
        $this->apiKey = $apiKey;
    }

    protected function getSpec(): Specification
    {
        return Spec::countOf(Spec::andX(
            Spec::isNotNull('shortUrl'),
            new WithApiKeySpecsEnsuringJoin($this->apiKey, 'shortUrl'),
        ));
    }
}
