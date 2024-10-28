<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl;

use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlWithVisitsSummary;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface ShortUrlListServiceInterface
{
    /**
     * @return Paginator<ShortUrlWithVisitsSummary>
     */
    public function listShortUrls(ShortUrlsParams $params, ApiKey|null $apiKey = null): Paginator;
}
