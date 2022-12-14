<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Repository;

interface CrawlableShortCodesQueryInterface
{
    /**
     * @return iterable<string>
     */
    public function __invoke(): iterable;
}
