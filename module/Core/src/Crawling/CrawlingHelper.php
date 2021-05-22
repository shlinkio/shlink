<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Crawling;

class CrawlingHelper implements CrawlingHelperInterface
{
    public function listCrawlableShortCodes(): iterable
    {
        return [];
    }
}
