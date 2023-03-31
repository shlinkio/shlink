<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\ApiKey\Spec;

use Happyr\DoctrineSpecification\Spec;
use Happyr\DoctrineSpecification\Specification\BaseSpecification;
use Happyr\DoctrineSpecification\Specification\Specification;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class WithApiKeySpecsEnsuringJoin extends BaseSpecification
{
    public function __construct(private readonly ?ApiKey $apiKey, private readonly string $fieldToJoin = 'shortUrls')
    {
        parent::__construct();
    }

    protected function getSpec(): Specification
    {
        return $this->apiKey === null || ApiKey::isAdmin($this->apiKey) ? Spec::andX() : Spec::andX(
            Spec::join($this->fieldToJoin, 's'),
            $this->apiKey->spec($this->fieldToJoin),
        );
    }
}
