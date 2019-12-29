<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Service;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Shlinkio\Shlink\Core\Util\UrlValidatorInterface;
use Zend\Diactoros\Uri;

use function array_map;

class UrlShortenerTest extends TestCase
{
    private UrlShortener $urlShortener;
    private ObjectProphecy $em;
    private ObjectProphecy $urlValidator;

    public function setUp(): void
    {
        $this->urlValidator = $this->prophesize(UrlValidatorInterface::class);

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
        $repo->shortCodeIsInUse(Argument::cetera())->willReturn(false);
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $this->setUrlShortener(false);
    }

    private function setUrlShortener(bool $urlValidationEnabled): void
    {
        $this->urlShortener = new UrlShortener(
            $this->urlValidator->reveal(),
            $this->em->reveal(),
            new UrlShortenerOptions(['validate_url' => $urlValidationEnabled])
        );
    }

    /** @test */
    public function urlIsProperlyShortened(): void
    {
        $shortUrl = $this->urlShortener->urlToShortCode(
            new Uri('http://foobar.com/12345/hello?foo=bar'),
            [],
            ShortUrlMeta::createEmpty()
        );

        $this->assertEquals('http://foobar.com/12345/hello?foo=bar', $shortUrl->getLongUrl());
    }

    /** @test */
    public function shortCodeIsRegeneratedIfAlreadyInUse(): void
    {
        $callIndex = 0;
        $expectedCalls = 3;
        $repo = $this->prophesize(ShortUrlRepository::class);
        $shortCodeIsInUse = $repo->shortCodeIsInUse(Argument::cetera())->will(
            function () use (&$callIndex, $expectedCalls) {
                $callIndex++;
                return $callIndex < $expectedCalls;
            }
        );
        $repo->findBy(Argument::cetera())->willReturn([]);
        $getRepo = $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $shortUrl = $this->urlShortener->urlToShortCode(
            new Uri('http://foobar.com/12345/hello?foo=bar'),
            [],
            ShortUrlMeta::createEmpty()
        );

        $this->assertEquals('http://foobar.com/12345/hello?foo=bar', $shortUrl->getLongUrl());
        $getRepo->shouldBeCalledTimes($expectedCalls);
        $shortCodeIsInUse->shouldBeCalledTimes($expectedCalls);
    }

    /** @test */
    public function transactionIsRolledBackAndExceptionRethrownWhenExceptionIsThrown(): void
    {
        $conn = $this->prophesize(Connection::class);
        $conn->isTransactionActive()->willReturn(true);
        $this->em->getConnection()->willReturn($conn->reveal());
        $this->em->rollback()->shouldBeCalledOnce();
        $this->em->close()->shouldBeCalledOnce();

        $this->em->flush()->willThrow(new ORMException());

        $this->expectException(ORMException::class);
        $this->urlShortener->urlToShortCode(
            new Uri('http://foobar.com/12345/hello?foo=bar'),
            [],
            ShortUrlMeta::createEmpty()
        );
    }

    /** @test */
    public function validatorIsCalledWhenUrlValidationIsEnabled(): void
    {
        $this->setUrlShortener(true);
        $validateUrl = $this->urlValidator->validateUrl('http://foobar.com/12345/hello?foo=bar')->will(function () {
        });

        $this->urlShortener->urlToShortCode(
            new Uri('http://foobar.com/12345/hello?foo=bar'),
            [],
            ShortUrlMeta::createEmpty()
        );

        $validateUrl->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function exceptionIsThrownWhenNonUniqueSlugIsProvided(): void
    {
        $repo = $this->prophesize(ShortUrlRepository::class);
        $shortCodeIsInUse = $repo->shortCodeIsInUse('custom-slug', null)->willReturn(true);
        $repo->findBy(Argument::cetera())->willReturn([]);
        $getRepo = $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $shortCodeIsInUse->shouldBeCalledOnce();
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
        ShortUrl $expected
    ): void {
        $repo = $this->prophesize(ShortUrlRepository::class);
        $findExisting = $repo->findBy(Argument::any())->willReturn([$expected]);
        $getRepo = $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $result = $this->urlShortener->urlToShortCode(new Uri($url), $tags, $meta);

        $findExisting->shouldHaveBeenCalledOnce();
        $getRepo->shouldHaveBeenCalledOnce();
        $this->em->persist(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->assertSame($expected, $result);
    }

    public function provideExistingShortUrls(): iterable
    {
        $url = 'http://foo.com';

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
            [],
            ShortUrlMeta::createFromRawData(['findIfExists' => true, 'domain' => 'example.com']),
            new ShortUrl($url, ShortUrlMeta::createFromRawData(['domain' => 'example.com'])),
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
        $shortUrl = new ShortUrl('expected_url');
        $shortCode = $shortUrl->getShortCode();

        $repo = $this->prophesize(ShortUrlRepositoryInterface::class);
        $repo->findOneByShortCode($shortCode, null)->willReturn($shortUrl);
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $url = $this->urlShortener->shortCodeToUrl($shortCode);
        $this->assertSame($shortUrl, $url);
    }
}
