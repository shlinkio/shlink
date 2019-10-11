<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

class ShortCodeCannotBeRegeneratedException extends RuntimeException
{
    public static function forShortUrlWithCustomSlug(): self
    {
        return new self('The short code cannot be regenerated on ShortUrls where a custom slug was provided.');
    }

    public static function forShortUrlAlreadyPersisted(): self
    {
        return new self('The short code can be regenerated only on new ShortUrls which have not been persisted yet.');
    }
}
