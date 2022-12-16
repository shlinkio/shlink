<?php

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use function Functional\contains;
use function Functional\map;

enum OrderableField: string
{
    case LONG_URL = 'longUrl';
    case SHORT_CODE = 'shortCode';
    case DATE_CREATED = 'dateCreated';
    case TITLE = 'title';
    case VISITS = 'visits';
    case NON_BOT_VISITS = 'nonBotVisits';

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return map(self::cases(), static fn (OrderableField $field) => $field->value);
    }

    public static function isBasicField(string $value): bool
    {
        return contains(
            [self::LONG_URL->value, self::SHORT_CODE->value, self::DATE_CREATED->value, self::TITLE->value],
            $value,
        );
    }

    public static function isVisitsField(string $value): bool
    {
        return $value === self::VISITS->value || $value === self::NON_BOT_VISITS->value;
    }
}
