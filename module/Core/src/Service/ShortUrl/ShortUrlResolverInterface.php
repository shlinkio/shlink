<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service\ShortUrl;

use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface ShortUrlResolverInterface
{
    /**
     * @throws ShortUrlNotFoundException
     */
    public function resolveShortUrl(ShortUrlIdentifier $identifier, ?ApiKey $apiKey = null): ShortUrl;

    /**
     * @throws ShortUrlNotFoundException
     */
    public function resolveEnabledShortUrl(ShortUrlIdentifier $identifier): ShortUrl;
}
