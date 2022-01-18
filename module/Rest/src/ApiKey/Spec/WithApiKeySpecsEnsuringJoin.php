<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\ApiKey\Spec;

use Happyr\DoctrineSpecification\Spec;
use Happyr\DoctrineSpecification\Specification\BaseSpecification;
use Happyr\DoctrineSpecification\Specification\Specification;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class WithApiKeySpecsEnsuringJoin extends BaseSpecification
{
    public function __construct(
        private ?ApiKey $apiKey,
        private string $fieldToJoin = 'shortUrls',
        private bool $inlined = false,
    ) {
        parent::__construct();
    }

    protected function getSpec(): Specification
    {
        return $this->apiKey === null || $this->apiKey->isAdmin() ? Spec::andX() : Spec::andX(
            Spec::join($this->fieldToJoin, 's'),
            $this->inlined ? $this->apiKey->inlinedSpec() : $this->apiKey->spec($this->fieldToJoin),
        );
    }
}
