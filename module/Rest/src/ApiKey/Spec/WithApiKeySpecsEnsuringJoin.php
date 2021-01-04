<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\ApiKey\Spec;

use Happyr\DoctrineSpecification\BaseSpecification;
use Happyr\DoctrineSpecification\Spec;
use Happyr\DoctrineSpecification\Specification\Specification;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class WithApiKeySpecsEnsuringJoin extends BaseSpecification
{
    private ?ApiKey $apiKey;

    public function __construct(?ApiKey $apiKey)
    {
        parent::__construct();
        $this->apiKey = $apiKey;
    }

    protected function getSpec(): Specification
    {
        return $this->apiKey === null || $this->apiKey->isAdmin() ? Spec::andX() : Spec::andX(
            Spec::join('shortUrls', 's'),
            $this->apiKey->spec(),
        );
    }
}
