<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Crawling;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Crawling\CrawlingHelper;
use Shlinkio\Shlink\Core\ShortUrl\Repository\CrawlableShortCodesQueryInterface;

class CrawlingHelperTest extends TestCase
{
    private CrawlingHelper $helper;
    private MockObject & CrawlableShortCodesQueryInterface $query;

    protected function setUp(): void
    {
        $this->query = $this->createMock(CrawlableShortCodesQueryInterface::class);
        $this->helper = new CrawlingHelper($this->query);
    }

    /** @test */
    public function listCrawlableShortCodesDelegatesIntoRepository(): void
    {
        $this->query->expects($this->once())->method('__invoke')->willReturn([]);
        [...$this->helper->listCrawlableShortCodes()];
    }
}
