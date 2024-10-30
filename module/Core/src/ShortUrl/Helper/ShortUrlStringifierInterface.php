<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Helper;

use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;

interface ShortUrlStringifierInterface
{
    public function stringify(ShortUrl|ShortUrlIdentifier $shortUrl): string;
}
