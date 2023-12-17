<?php

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use function Shlinkio\Shlink\Core\ArrayUtils\contains;

enum OrderableField: string
{
    case LONG_URL = 'longUrl';
    case SHORT_CODE = 'shortCode';
    case DATE_CREATED = 'dateCreated';
    case TITLE = 'title';
    case VISITS = 'visits';
    case NON_BOT_VISITS = 'nonBotVisits';

    public static function isBasicField(string $value): bool
    {
        return contains(
            $value,
            [self::LONG_URL->value, self::SHORT_CODE->value, self::DATE_CREATED->value, self::TITLE->value],
        );
    }

    public static function isVisitsField(string $value): bool
    {
        return $value === self::VISITS->value || $value === self::NON_BOT_VISITS->value;
    }
}
