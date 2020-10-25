<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Util;

interface DoctrineBatchHelperInterface
{
    public function wrapIterable(iterable $resultSet, int $batchSize): iterable;
}
