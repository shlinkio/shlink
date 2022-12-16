<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use function Functional\map;

enum TagsMode: string
{
    case ANY = 'any';
    case ALL = 'all';

    public static function values(): array
    {
        return map(self::cases(), static fn (TagsMode $mode) => $mode->value);
    }
}
