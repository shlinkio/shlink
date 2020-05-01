<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Model;

use JsonSerializable;

final class VisitsStats implements JsonSerializable
{
    private int $visitsCount;

    public function __construct(int $visitsCount)
    {
        $this->visitsCount = $visitsCount;
    }

    public function jsonSerialize(): array
    {
        return [
            'visitsCount' => $this->visitsCount,
        ];
    }
}
