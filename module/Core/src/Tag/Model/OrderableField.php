<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Model;

use function Shlinkio\Shlink\Core\camelCaseToSnakeCase;

enum OrderableField: string
{
    case TAG = 'tag';
    case SHORT_URLS_COUNT = 'shortUrlsCount';
    case VISITS = 'visits';
    case NON_BOT_VISITS = 'nonBotVisits';
    /** @deprecated Use VISITS instead */
    case VISITS_COUNT = 'visitsCount';

    public static function isAggregateField(string $field): bool
    {
        $parsed = self::tryFrom($field);
        return $parsed !== null && $parsed !== self::TAG;
    }

    public static function toSnakeCaseValidField(?string $field): string
    {
        $parsed = self::tryFrom($field);
        $normalized = match ($parsed) {
            self::VISITS_COUNT, null => self::VISITS,
            default => $parsed,
        };

        return camelCaseToSnakeCase($normalized->value);
    }
}
