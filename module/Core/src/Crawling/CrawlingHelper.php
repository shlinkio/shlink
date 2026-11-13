<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Crawling;

use Override;
use Shlinkio\Shlink\Core\ShortUrl\Repository\CrawlableShortCodesQueryInterface;

readonly class CrawlingHelper implements CrawlingHelperInterface
{
    public function __construct(private CrawlableShortCodesQueryInterface $query) {}

    #[Override]
    public function listCrawlableShortCodes(): iterable
    {
        yield from ($this->query)();
    }
}
