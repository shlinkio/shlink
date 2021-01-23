<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Paginator\Adapter;

use Pagerfanta\Adapter\AdapterInterface;

abstract class AbstractCacheableCountPaginatorAdapter implements AdapterInterface
{
    private ?int $count = null;

    final public function getNbResults(): int
    {
        // Since a new adapter instance is created every time visits are fetched, it is reasonably safe to internally
        // cache the count value.
        // The reason it is cached is because the Paginator is actually calling the method twice.
        // An inconsistent value could be returned if between the first call and the second one, a new visit is created.
        // However, it's almost instant, and then the adapter instance is discarded immediately after.

        if ($this->count !== null) {
            return $this->count;
        }

        return $this->count = $this->doCount();
    }

    abstract protected function doCount(): int;
}
