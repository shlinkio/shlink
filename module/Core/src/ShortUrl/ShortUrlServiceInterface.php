<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl;

use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlEdition;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlsParams;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface ShortUrlServiceInterface
{
    /**
     * @return ShortUrl[]|Paginator
     */
    public function listShortUrls(ShortUrlsParams $params, ?ApiKey $apiKey = null): Paginator;

    /**
     * @throws ShortUrlNotFoundException
     * @throws InvalidUrlException
     */
    public function updateShortUrl(
        ShortUrlIdentifier $identifier,
        ShortUrlEdition $shortUrlEdit,
        ?ApiKey $apiKey = null,
    ): ShortUrl;
}
