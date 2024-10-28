<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Model;

enum OrderableField: string
{
    case TAG = 'tag';
    case SHORT_URLS_COUNT = 'shortUrlsCount';
    case VISITS = 'visits';
    case NON_BOT_VISITS = 'nonBotVisits';

    public static function toValidField(string|null $field): self
    {
        if ($field === null) {
            return self::TAG;
        }

        return self::tryFrom($field) ?? self::TAG;
    }
}
