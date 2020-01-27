<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Service;

use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\Service\ShortUrlService;

use function count;

class ShortUrlServiceTest extends TestCase
{
    private ShortUrlService $service;
    private ObjectProphecy $em;

    public function setUp(): void
    {
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->em->persist(Argument::any())->willReturn(null);
        $this->em->flush()->willReturn(null);
        $this->service = new ShortUrlService($this->em->reveal());
    }

    /** @test */
    public function listedUrlsAreReturnedFromEntityManager(): void
    {
        $list = [
            new ShortUrl(''),
            new ShortUrl(''),
            new ShortUrl(''),
            new ShortUrl(''),
        ];

        $repo = $this->prophesize(ShortUrlRepository::class);
        $repo->findList(Argument::cetera())->willReturn($list)->shouldBeCalledOnce();
        $repo->countList(Argument::cetera())->willReturn(count($list))->shouldBeCalledOnce();
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $list = $this->service->listShortUrls();
        $this->assertEquals(4, $list->getCurrentItemCount());
    }

    /** @test */
    public function exceptionIsThrownWhenSettingTagsOnInvalidShortcode(): void
    {
        $shortCode = 'abc123';
        $repo = $this->prophesize(ShortUrlRepository::class);
        $repo->findOneBy(['shortCode' => $shortCode])->willReturn(null)
                                                     ->shouldBeCalledOnce();
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $this->expectException(ShortUrlNotFoundException::class);
        $this->service->setTagsByShortCode($shortCode);
    }

    /** @test */
    public function providedTagsAreGetFromRepoAndSetToTheShortUrl(): void
    {
        $shortUrl = $this->prophesize(ShortUrl::class);
        $shortUrl->setTags(Argument::any())->shouldBeCalledOnce();
        $shortCode = 'abc123';
        $repo = $this->prophesize(ShortUrlRepository::class);
        $repo->findOneBy(['shortCode' => $shortCode])->willReturn($shortUrl->reveal())
                                                     ->shouldBeCalledOnce();
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $tagRepo = $this->prophesize(EntityRepository::class);
        $tagRepo->findOneBy(['name' => 'foo'])->willReturn(new Tag('foo'))->shouldBeCalledOnce();
        $tagRepo->findOneBy(['name' => 'bar'])->willReturn(null)->shouldBeCalledOnce();
        $this->em->getRepository(Tag::class)->willReturn($tagRepo->reveal());

        $this->service->setTagsByShortCode($shortCode, ['foo', 'bar']);
    }

    /** @test */
    public function updateMetadataByShortCodeUpdatesProvidedData(): void
    {
        $shortUrl = new ShortUrl('');

        $repo = $this->prophesize(ShortUrlRepository::class);
        $findShortUrl = $repo->findOneBy(['shortCode' => 'abc123'])->willReturn($shortUrl);
        $getRepo = $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());
        $flush = $this->em->flush()->willReturn(null);

        $result = $this->service->updateMetadataByShortCode('abc123', ShortUrlMeta::fromRawData([
            'validSince' => Chronos::parse('2017-01-01 00:00:00')->toAtomString(),
            'validUntil' => Chronos::parse('2017-01-05 00:00:00')->toAtomString(),
            'maxVisits' => 5,
        ]));

        $this->assertSame($shortUrl, $result);
        $this->assertEquals(Chronos::parse('2017-01-01 00:00:00'), $shortUrl->getValidSince());
        $this->assertEquals(Chronos::parse('2017-01-05 00:00:00'), $shortUrl->getValidUntil());
        $this->assertEquals(5, $shortUrl->getMaxVisits());
        $findShortUrl->shouldHaveBeenCalled();
        $getRepo->shouldHaveBeenCalled();
        $flush->shouldHaveBeenCalled();
    }
}
