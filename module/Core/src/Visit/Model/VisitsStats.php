<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Model;

use JsonSerializable;

final class VisitsStats implements JsonSerializable
{
    private readonly VisitsSummary $nonOrphanVisitsSummary;
    private readonly VisitsSummary $orphanVisitsSummary;

    public function __construct(
        int $nonOrphanVisitsTotal,
        int $orphanVisitsTotal,
        ?int $nonOrphanVisitsNonBots = null,
        ?int $orphanVisitsNonBots = null,
    ) {
        $this->nonOrphanVisitsSummary = VisitsSummary::fromTotalAndNonBots(
            $nonOrphanVisitsTotal,
            $nonOrphanVisitsNonBots ?? $nonOrphanVisitsTotal,
        );
        $this->orphanVisitsSummary = VisitsSummary::fromTotalAndNonBots(
            $orphanVisitsTotal,
            $orphanVisitsNonBots ?? $orphanVisitsTotal,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'nonOrphanVisits' => $this->nonOrphanVisitsSummary,
            'orphanVisits' => $this->orphanVisitsSummary,

            // Deprecated
            'visitsCount' => $this->nonOrphanVisitsSummary->total,
            'orphanVisitsCount' => $this->orphanVisitsSummary->total,
        ];
    }
}
