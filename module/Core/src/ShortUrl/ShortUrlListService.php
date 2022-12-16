<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl;

use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\ShortUrl\Paginator\Adapter\ShortUrlRepositoryAdapter;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlListRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ShortUrlListService implements ShortUrlListServiceInterface
{
    public function __construct(
        private readonly ShortUrlListRepositoryInterface $repo,
        private readonly UrlShortenerOptions $urlShortenerOptions,
    ) {
    }

    /**
     * @return ShortUrl[]|Paginator
     */
    public function listShortUrls(ShortUrlsParams $params, ?ApiKey $apiKey = null): Paginator
    {
        $defaultDomain = $this->urlShortenerOptions->domain['hostname'] ?? '';
        $paginator = new Paginator(new ShortUrlRepositoryAdapter($this->repo, $params, $apiKey, $defaultDomain));
        $paginator->setMaxPerPage($params->itemsPerPage)
                  ->setCurrentPage($params->page);

        return $paginator;
    }
}
