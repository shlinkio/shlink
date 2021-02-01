<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Helper;

use Shlinkio\Shlink\Core\Entity\ShortUrl;

interface ShortUrlStringifierInterface
{
    public function stringify(ShortUrl $shortUrl): string;
}
