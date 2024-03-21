<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Model\VisitsSummary;

final readonly class ShortUrlWithVisitsSummary
{
    private function __construct(public ShortUrl $shortUrl, public VisitsSummary $visitsSummary)
    {
    }

    /**
     * @param array{shortUrl: ShortUrl, visitsCount: string|int, nonBotVisitsCount: string|int} $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['shortUrl'], VisitsSummary::fromTotalAndNonBots(
            (int) $data['visitsCount'],
            (int) $data['nonBotVisitsCount'],
        ));
    }

    public function toArray(): array
    {
        return $this->shortUrl->toArray($this->visitsSummary);
    }
}
