<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Service;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Exception\RuntimeException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Zend\Diactoros\Uri;

use function array_map;

class UrlShortenerTest extends TestCase
{
    /** @var UrlShortener */
    private $urlShortener;
    /** @var ObjectProphecy */
    private $em;
    /** @var ObjectProphecy */
    private $httpClient;

    public function setUp(): void
    {
        $this->httpClient = $this->prophesize(ClientInterface::class);

        $this->em = $this->prophesize(EntityManagerInterface::class);
        $conn = $this->prophesize(Connection::class);
        $conn->isTransactionActive()->willReturn(false);
        $this->em->getConnection()->willReturn($conn->reveal());
        $this->em->flush()->willReturn(null);
        $this->em->commit()->willReturn(null);
        $this->em->beginTransaction()->willReturn(null);
        $this->em->persist(Argument::any())->will(function ($arguments) {
            /** @var ShortUrl $shortUrl */
            $shortUrl = $arguments[0];
            $shortUrl->setId('10');
        });
        $repo = $this->prophesize(ShortUrlRepository::class);
        $repo->count(Argument::any())->willReturn(0);
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $this->setUrlShortener(false);
    }

    private function setUrlShortener(bool $urlValidationEnabled): void
    {
        $this->urlShortener = new UrlShortener(
            $this->httpClient->reveal(),
            $this->em->reveal(),
            new UrlShortenerOptions(['validate_url' => $urlValidationEnabled])
        );
    }

    /** @test */
    public function urlIsProperlyShortened(): void
    {
        // 10 -> 0Q1Y
        $shortUrl = $this->urlShortener->urlToShortCode(
            new Uri('http://foobar.com/12345/hello?foo=bar'),
            [],
            ShortUrlMeta::createEmpty()
        );
        $this->assertEquals('0Q1Y', $shortUrl->getShortCode());
    }

    /** @test */
    public function exceptionIsThrownWhenOrmThrowsException(): void
    {
        $conn = $this->prophesize(Connection::class);
        $conn->isTransactionActive()->willReturn(true);
        $this->em->getConnection()->willReturn($conn->reveal());
        $this->em->rollback()->shouldBeCalledOnce();
        $this->em->close()->shouldBeCalledOnce();

        $this->em->flush()->willThrow(new ORMException());

        $this->expectException(RuntimeException::class);
        $this->urlShortener->urlToShortCode(
            new Uri('http://foobar.com/12345/hello?foo=bar'),
            [],
            ShortUrlMeta::createEmpty()
        );
    }

    /** @test */
    public function exceptionIsThrownWhenUrlDoesNotExist(): void
    {
        $this->setUrlShortener(true);

        $this->httpClient->request(Argument::cetera())->willThrow(
            new ClientException('', $this->prophesize(Request::class)->reveal())
        );

        $this->expectException(InvalidUrlException::class);
        $this->urlShortener->urlToShortCode(
            new Uri('http://foobar.com/12345/hello?foo=bar'),
            [],
            ShortUrlMeta::createEmpty()
        );
    }

    /** @test */
    public function exceptionIsThrownWhenNonUniqueSlugIsProvided(): void
    {
        $repo = $this->prophesize(ShortUrlRepository::class);
        $countBySlug = $repo->count(['shortCode' => 'custom-slug'])->willReturn(1);
        $repo->findBy(Argument::cetera())->willReturn([]);
        $getRepo = $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $countBySlug->shouldBeCalledOnce();
        $getRepo->shouldBeCalled();
        $this->expectException(NonUniqueSlugException::class);

        $this->urlShortener->urlToShortCode(
            new Uri('http://foobar.com/12345/hello?foo=bar'),
            [],
            ShortUrlMeta::createFromRawData(['customSlug' => 'custom-slug'])
        );
    }

    /**
     * @test
     * @dataProvider provideExistingShortUrls
     */
    public function existingShortUrlIsReturnedWhenRequested(
        string $url,
        array $tags,
        ShortUrlMeta $meta,
        ?ShortUrl $expected
    ): void {
        $repo = $this->prophesize(ShortUrlRepository::class);
        $findExisting = $repo->findBy(Argument::any())->willReturn($expected !== null ? [$expected] : []);
        $getRepo = $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $result = $this->urlShortener->urlToShortCode(new Uri($url), $tags, $meta);

        $findExisting->shouldHaveBeenCalledOnce();
        $getRepo->shouldHaveBeenCalledOnce();
        if ($expected) {
            $this->assertSame($expected, $result);
        }
    }

    public function provideExistingShortUrls(): iterable
    {
        $url = 'http://foo.com';

        yield [$url, [], ShortUrlMeta::createFromRawData(['findIfExists' => true]), null];
        yield [$url, [], ShortUrlMeta::createFromRawData(['findIfExists' => true]), new ShortUrl($url)];
        yield [$url, [], ShortUrlMeta::createFromRawData(
            ['findIfExists' => true, 'customSlug' => 'foo']
        ), new ShortUrl($url)];
        yield [
            $url,
            ['foo', 'bar'],
            ShortUrlMeta::createFromRawData(['findIfExists' => true]),
            (new ShortUrl($url))->setTags(new ArrayCollection([new Tag('bar'), new Tag('foo')])),
        ];
        yield [
            $url,
            [],
            ShortUrlMeta::createFromRawData(['findIfExists' => true, 'maxVisits' => 3]),
            new ShortUrl($url, ShortUrlMeta::createFromRawData(['maxVisits' => 3])),
        ];
        yield [
            $url,
            [],
            ShortUrlMeta::createFromRawData(['findIfExists' => true, 'validSince' => Chronos::parse('2017-01-01')]),
            new ShortUrl($url, ShortUrlMeta::createFromRawData(['validSince' => Chronos::parse('2017-01-01')])),
        ];
        yield [
            $url,
            [],
            ShortUrlMeta::createFromRawData(['findIfExists' => true, 'validUntil' => Chronos::parse('2017-01-01')]),
            new ShortUrl($url, ShortUrlMeta::createFromRawData(['validUntil' => Chronos::parse('2017-01-01')])),
        ];
        yield [
            $url,
            ['baz', 'foo', 'bar'],
            ShortUrlMeta::createFromRawData([
                'findIfExists' => true,
                'validUntil' => Chronos::parse('2017-01-01'),
                'maxVisits' => 4,
            ]),
            (new ShortUrl($url, ShortUrlMeta::createFromRawData([
                'validUntil' => Chronos::parse('2017-01-01'),
                'maxVisits' => 4,
            ])))->setTags(new ArrayCollection([new Tag('foo'), new Tag('bar'), new Tag('baz')])),
        ];
    }

    /** @test */
    public function properExistingShortUrlIsReturnedWhenMultipleMatch(): void
    {
        $url = 'http://foo.com';
        $tags = ['baz', 'foo', 'bar'];
        $meta = ShortUrlMeta::createFromRawData([
            'findIfExists' => true,
            'validUntil' => Chronos::parse('2017-01-01'),
            'maxVisits' => 4,
        ]);
        $tagsCollection = new ArrayCollection(array_map(function (string $tag) {
            return new Tag($tag);
        }, $tags));
        $expected = (new ShortUrl($url, $meta))->setTags($tagsCollection);

        $repo = $this->prophesize(ShortUrlRepository::class);
        $findExisting = $repo->findBy(Argument::any())->willReturn([
            new ShortUrl($url),
            new ShortUrl($url, $meta),
            $expected,
            (new ShortUrl($url))->setTags($tagsCollection),
        ]);
        $getRepo = $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $result = $this->urlShortener->urlToShortCode(new Uri($url), $tags, $meta);

        $this->assertSame($expected, $result);
        $findExisting->shouldHaveBeenCalledOnce();
        $getRepo->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function shortCodeIsProperlyParsed(): void
    {
        $shortCode = '12C1c';
        $shortUrl = new ShortUrl('expected_url');
        $shortUrl->setShortCode($shortCode);

        $repo = $this->prophesize(ShortUrlRepositoryInterface::class);
        $repo->findOneByShortCode($shortCode)->willReturn($shortUrl);
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $url = $this->urlShortener->shortCodeToUrl($shortCode);
        $this->assertSame($shortUrl, $url);
    }

    /** @test */
    public function invalidCharSetThrowsException(): void
    {
        $this->expectException(InvalidShortCodeException::class);
        $this->urlShortener->shortCodeToUrl('&/(');
    }
}
