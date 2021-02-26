<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\ApiKey\Spec;

use Happyr\DoctrineSpecification\Spec;
use Happyr\DoctrineSpecification\Specification\BaseSpecification;
use Happyr\DoctrineSpecification\Specification\Specification;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class WithApiKeySpecsEnsuringJoin extends BaseSpecification
{
    private ?ApiKey $apiKey;
    private string $fieldToJoin;

    public function __construct(?ApiKey $apiKey, string $fieldToJoin = 'shortUrls')
    {
        parent::__construct();
        $this->apiKey = $apiKey;
        $this->fieldToJoin = $fieldToJoin;
    }

    protected function getSpec(): Specification
    {
        return $this->apiKey === null || $this->apiKey->isAdmin() ? Spec::andX() : Spec::andX(
            Spec::join($this->fieldToJoin, 's'),
            $this->apiKey->spec(false, $this->fieldToJoin),
        );
    }
}
