<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Crawling;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Crawling\CrawlingHelper;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;

class CrawlingHelperTest extends TestCase
{
    use ProphecyTrait;

    private CrawlingHelper $helper;
    private ObjectProphecy $em;

    protected function setUp(): void
    {
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->helper = new CrawlingHelper($this->em->reveal());
    }

    /** @test */
    public function listCrawlableShortCodesDelegatesIntoRepository(): void
    {
        $repo = $this->prophesize(ShortUrlRepositoryInterface::class);
        $findCrawlableShortCodes = $repo->findCrawlableShortCodes()->willReturn([]);
        $getRepo = $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $result = $this->helper->listCrawlableShortCodes();
        foreach ($result as $shortCode) {
            // Result is a generator and therefore, it needs to be iterated
        }

        $findCrawlableShortCodes->shouldHaveBeenCalledOnce();
        $getRepo->shouldHaveBeenCalledOnce();
    }
}
