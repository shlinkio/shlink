<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Model;

use JsonSerializable;

final class VisitsSummary implements JsonSerializable
{
    private function __construct(public readonly int $total, public readonly int $nonBots)
    {
    }

    public static function fromTotalAndNonBots(int $total, int $nonBots): self
    {
        return new self($total, $nonBots);
    }

    public function jsonSerialize(): array
    {
        return [
            'total' => $this->total,
            'nonBots' => $this->nonBots,
            'bots' => $this->total - $this->nonBots,
        ];
    }
}
