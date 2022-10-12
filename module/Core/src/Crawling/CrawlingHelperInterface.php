<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Crawling;

interface CrawlingHelperInterface
{
    /**
     * @return iterable<string>
     */
    public function listCrawlableShortCodes(): iterable;
}
