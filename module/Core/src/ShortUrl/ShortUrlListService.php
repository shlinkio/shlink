<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl;

use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\ShortUrl\Paginator\Adapter\ShortUrlRepositoryAdapter;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlListRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

readonly class ShortUrlListService implements ShortUrlListServiceInterface
{
    public function __construct(
        private ShortUrlListRepositoryInterface $repo,
        private UrlShortenerOptions $urlShortenerOptions,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function listShortUrls(ShortUrlsParams $params, ?ApiKey $apiKey = null): Paginator
    {
        $defaultDomain = $this->urlShortenerOptions->defaultDomain();
        $paginator = new Paginator(new ShortUrlRepositoryAdapter($this->repo, $params, $apiKey, $defaultDomain));
        $paginator->setMaxPerPage($params->itemsPerPage)
                  ->setCurrentPage($params->page);

        return $paginator;
    }
}
