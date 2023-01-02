<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Model;

use function Shlinkio\Shlink\Core\camelCaseToSnakeCase;

enum OrderableField: string
{
    case TAG = 'tag';
//    case SHORT_URLS = 'shortUrls';
//    case VISITS = 'visits';
//    case NON_BOT_VISITS = 'nonBotVisits';

    /** @deprecated Use VISITS instead */
    case VISITS_COUNT = 'visitsCount';
    /** @deprecated Use SHORT_URLS instead */
    case SHORT_URLS_COUNT = 'shortUrlsCount';

    public static function isAggregateField(string $field): bool
    {
        return $field === self::SHORT_URLS_COUNT->value || $field === self::VISITS_COUNT->value;
    }

    public static function toSnakeCaseValidField(?string $field): string
    {
        return camelCaseToSnakeCase($field === self::SHORT_URLS_COUNT->value ? $field : self::VISITS_COUNT->value);
    }
}
