<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

final class BulkDeleteResult
{
    public function __construct(public readonly int $affectedItems)
    {
    }

    public function toArray(string $fieldName): array
    {
        return [$fieldName => $this->affectedItems];
    }
}
