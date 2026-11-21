<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Helper;

use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;

interface ShortUrlStringifierInterface
{
    /**
     * Stringify a short URL to its fully qualified URI representation
     */
    public function stringify(ShortUrl|ShortUrlIdentifier $shortUrl): string;

    /**
     * Resolve the domain authority of a short URL, or the default domain
     */
    public function domain(ShortUrl|ShortUrlIdentifier $shortUrlIdentifier): string;
}
