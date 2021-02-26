<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Spec;

use Happyr\DoctrineSpecification\Spec;
use Happyr\DoctrineSpecification\Specification\BaseSpecification;
use Happyr\DoctrineSpecification\Specification\Specification;

class CountTagsWithName extends BaseSpecification
{
    private string $tagName;

    public function __construct(string $tagName)
    {
        parent::__construct();
        $this->tagName = $tagName;
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
