<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Crawling;

interface CrawlingHelperInterface
{
    /**
     * @return string[]|iterable
     */
    public function listCrawlableShortCodes(): iterable;
}
