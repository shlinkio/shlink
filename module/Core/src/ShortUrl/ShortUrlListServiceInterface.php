<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl;

use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlsParams;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface ShortUrlListServiceInterface
{
    /**
     * @return ShortUrl[]|Paginator
     */
    public function listShortUrls(ShortUrlsParams $params, ?ApiKey $apiKey = null): Paginator;
}
