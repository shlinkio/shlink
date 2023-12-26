<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl;

use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface ShortUrlResolverInterface
{
    /**
     * @throws ShortUrlNotFoundException
     */
    public function resolveShortUrl(ShortUrlIdentifier $identifier, ?ApiKey $apiKey = null): ShortUrl;

    /**
     * Resolves a public short URL matching provided identifier.
     * When trying to match public short URLs, if provided domain is default one, it gets ignored.
     * If provided domain is not default, but the short code is found in default domain, we fall back to that short URL.
     *
     * @throws ShortUrlNotFoundException
     */
    public function resolvePublicShortUrl(ShortUrlIdentifier $identifier): ShortUrl;

    /**
     * Resolves a public short URL matching provided identifier, only if it's not disabled.
     * Disabled short URLs are those which received the max amount of visits, have a `validSince` in the future or have
     * a `validUntil` in the past.
     *
     * @throws ShortUrlNotFoundException
     */
    public function resolveEnabledShortUrl(ShortUrlIdentifier $identifier): ShortUrl;
}
