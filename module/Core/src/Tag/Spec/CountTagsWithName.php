<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Spec;

use Happyr\DoctrineSpecification\Spec;
use Happyr\DoctrineSpecification\Specification\BaseSpecification;
use Happyr\DoctrineSpecification\Specification\Specification;

class CountTagsWithName extends BaseSpecification
{
    public function __construct(private string $tagName)
    {
        parent::__construct();
    }

    protected function getSpec(): Specification
    {
        return Spec::countOf(
            Spec::andX(
                Spec::select('id'),
                Spec::eq('name', $this->tagName),
            ),
        );
    }
}
