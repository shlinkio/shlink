<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Crawling;

use Shlinkio\Shlink\Core\ShortUrl\Repository\CrawlableShortCodesQueryInterface;

class CrawlingHelper implements CrawlingHelperInterface
{
    public function __construct(private readonly CrawlableShortCodesQueryInterface $query)
    {
    }

    public function listCrawlableShortCodes(): iterable
    {
        yield from ($this->query)();
    }
}
