<?php

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

enum ShortUrlMode: string
{
    case STRICT = 'strict';
    case LOOSE = 'loose';

    /** @deprecated */
    public static function tryDeprecated(string $mode): ?self
    {
        return $mode === 'loosely' ? self::LOOSE : self::tryFrom($mode);
    }
}
