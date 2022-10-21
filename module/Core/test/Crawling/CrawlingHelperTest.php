<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Crawling;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Crawling\CrawlingHelper;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepositoryInterface;

class CrawlingHelperTest extends TestCase
{
    private CrawlingHelper $helper;
    private MockObject $em;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->helper = new CrawlingHelper($this->em);
    }

    /** @test */
    public function listCrawlableShortCodesDelegatesIntoRepository(): void
    {
        $repo = $this->createMock(ShortUrlRepositoryInterface::class);
        $repo->expects($this->once())->method('findCrawlableShortCodes')->willReturn([]);
        $this->em->expects($this->once())->method('getRepository')->with($this->equalTo(ShortUrl::class))->willReturn(
            $repo,
        );

        $result = $this->helper->listCrawlableShortCodes();
        foreach ($result as $shortCode) {
            // $result is a generator and therefore, it needs to be iterated
        }
    }
}
