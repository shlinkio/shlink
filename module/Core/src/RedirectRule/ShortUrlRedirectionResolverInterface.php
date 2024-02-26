<?php

namespace Shlinkio\Shlink\Core\RedirectRule;

use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

interface ShortUrlRedirectionResolverInterface
{
    public function resolveLongUrl(ShortUrl $shortUrl, ServerRequestInterface $request): string;
}
