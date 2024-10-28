<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Model;

use JsonSerializable;
use Shlinkio\Shlink\Core\Visit\Model\VisitsSummary;

final readonly class TagInfo implements JsonSerializable
{
    public VisitsSummary $visitsSummary;

    public function __construct(
        public string $tag,
        public int $shortUrlsCount,
        int $visitsCount,
        int|null $nonBotVisitsCount = null,
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
        ];
    }
}
