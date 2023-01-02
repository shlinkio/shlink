<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Model;

use JsonSerializable;
use Shlinkio\Shlink\Core\Visit\Model\VisitsSummary;

final class TagInfo implements JsonSerializable
{
    public readonly VisitsSummary $visitsSummary;

    public function __construct(
        public readonly string $tag,
        public readonly int $shortUrlsCount,
        int $visitsCount,
        ?int $nonBotVisitsCount = null,
    ) {
        $this->visitsSummary = VisitsSummary::fromTotalAndNonBots($visitsCount, $nonBotVisitsCount ?? $visitsCount);
    }

    public static function fromRawData(array $data): self
    {
        return new self(
            $data['tag'],
            (int) $data['shortUrlsCount'],
            (int) $data['visits'],
            isset($data['nonBotVisits']) ? (int) $data['nonBotVisits'] : null,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'tag' => $this->tag,
            'shortUrlsCount' => $this->shortUrlsCount,
            'visitsSummary' => $this->visitsSummary,

            // Deprecated
            'visitsCount' => $this->visitsSummary->total,
        ];
    }
}
