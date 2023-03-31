<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Model;

enum OrderableField: string
{
    case TAG = 'tag';
    case SHORT_URLS_COUNT = 'shortUrlsCount';
    case VISITS = 'visits';
    case NON_BOT_VISITS = 'nonBotVisits';
    /** @deprecated Use VISITS instead */
    case VISITS_COUNT = 'visitsCount';

    public static function toSnakeCaseValidField(?string $field): self
    {
        $parsed = $field !== null ? self::tryFrom($field) : self::TAG;
        return match ($parsed) {
            self::VISITS_COUNT, null => self::VISITS,
            default => $parsed,
        };
    }
}
