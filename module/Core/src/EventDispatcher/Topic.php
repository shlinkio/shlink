<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher;

use function Shlinkio\Shlink\Core\enumNames;
use function sprintf;

enum Topic: string
{
    case NEW_VISIT = 'https://shlink.io/new-visit';
    case NEW_SHORT_URL_VISIT = 'https://shlink.io/new-visit/%s';
    case NEW_ORPHAN_VISIT = 'https://shlink.io/new-orphan-visit';
    case NEW_SHORT_URL = 'https://shlink.io/new-short-url';

    public static function newShortUrlVisit(string|null $shortCode): string
    {
        return sprintf(self::NEW_SHORT_URL_VISIT->value, $shortCode ?? '');
    }

    public static function allTopicNames(): array
    {
        return enumNames(self::class);
    }
}
