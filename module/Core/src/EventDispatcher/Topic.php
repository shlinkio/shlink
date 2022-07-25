<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher;

use function sprintf;

enum Topic: string
{
    case NEW_VISIT = 'https://shlink.io/new-visit';
    case NEW_ORPHAN_VISIT = 'https://shlink.io/new-orphan-visit';
    case NEW_SHORT_URL = 'https://shlink.io/new-short-url';

    public static function newShortUrlVisit(?string $shortCode): string
    {
        return sprintf('%s/%s', self::NEW_VISIT->value, $shortCode ?? '');
    }
}
