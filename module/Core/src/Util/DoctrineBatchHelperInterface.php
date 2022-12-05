<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Util;

interface DoctrineBatchHelperInterface
{
    /**
     * @template T
     * @param iterable<T> $resultSet
     * @return iterable<T>
     */
    public function wrapIterable(iterable $resultSet, int $batchSize): iterable;
}
