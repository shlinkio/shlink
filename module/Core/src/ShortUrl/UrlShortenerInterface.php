<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl;

use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\UrlShorteningResult;

interface UrlShortenerInterface
{
    /**
     * @throws NonUniqueSlugException
     */
    public function shorten(ShortUrlCreation $creation): UrlShorteningResult;
}
