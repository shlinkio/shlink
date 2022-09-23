<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl;

use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortCodeUniquenessHelperInterface;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlTitleResolutionHelperInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\SimpleShortUrlRelationResolver;
use Shlinkio\Shlink\Core\ShortUrl\UrlShortener;

class UrlShortenerTest extends TestCase
{
    use ProphecyTrait;

    private UrlShortener $urlShortener;
    private ObjectProphecy $em;
    private ObjectProphecy $titleResolutionHelper;
    private ObjectProphecy $shortCodeHelper;

    protected function setUp(): void
    {
        $this->titleResolutionHelper = $this->prophesize(ShortUrlTitleResolutionHelperInterface::class);
        $this->titleResolutionHelper->processTitleAndValidateUrl(Argument::cetera())->willReturnArgument();

        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->em->persist(Argument::any())->will(function ($arguments): void {
            /** @var ShortUrl $shortUrl */
            [$shortUrl] = $arguments;
            $shortUrl->setId('10');
        });
        $this->em->wrapInTransaction(Argument::type('callable'))->will(function (array $args) {
            /** @var callable $callback */
            [$callback] = $args;

            return $callback();
        });
        $repo = $this->prophesize(ShortUrlRepository::class);
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $this->shortCodeHelper = $this->prophesize(ShortCodeUniquenessHelperInterface::class);
        $this->shortCodeHelper->ensureShortCodeUniqueness(Argument::cetera())->willReturn(true);

        $this->urlShortener = new UrlShortener(
            $this->titleResolutionHelper->reveal(),
            $this->em->reveal(),
            new SimpleShortUrlRelationResolver(),
            $this->shortCodeHelper->reveal(),
            $this->prophesize(EventDispatcherInterface::class)->reveal(),
        );
    }

    /** @test */
    public function urlIsProperlyShortened(): void
    {
        $longUrl = 'http://foobar.com/12345/hello?foo=bar';
        $meta = ShortUrlCreation::fromRawData(['longUrl' => $longUrl]);
        $shortUrl = $this->urlShortener->shorten($meta);

        self::assertEquals($longUrl, $shortUrl->getLongUrl());
        $this->titleResolutionHelper->processTitleAndValidateUrl($meta)->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function exceptionIsThrownWhenNonUniqueSlugIsProvided(): void
    {
        $ensureUniqueness = $this->shortCodeHelper->ensureShortCodeUniqueness(Argument::cetera())->willReturn(false);

        $ensureUniqueness->shouldBeCalledOnce();
        $this->expectException(NonUniqueSlugException::class);

        $this->urlShortener->shorten(ShortUrlCreation::fromRawData(
            ['customSlug' => 'custom-slug', 'longUrl' => 'http://foobar.com/12345/hello?foo=bar'],
        ));
    }

    /**
     * @test
     * @dataProvider provideExistingShortUrls
     */
    public function existingShortUrlIsReturnedWhenRequested(ShortUrlCreation $meta, ShortUrl $expected): void
    {
        $repo = $this->prophesize(ShortUrlRepository::class);
        $findExisting = $repo->findOneMatching(Argument::cetera())->willReturn($expected);
        $getRepo = $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $result = $this->urlShortener->shorten($meta);

        $findExisting->shouldHaveBeenCalledOnce();
        $getRepo->shouldHaveBeenCalledOnce();
        $this->em->persist(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->titleResolutionHelper->processTitleAndValidateUrl(Argument::cetera())->shouldNotHaveBeenCalled();
        self::assertSame($expected, $result);
    }

    public function provideExistingShortUrls(): iterable
    {
        $url = 'http://foo.com';

        yield [ShortUrlCreation::fromRawData(['findIfExists' => true, 'longUrl' => $url]), ShortUrl::withLongUrl(
            $url,
        )];
        yield [ShortUrlCreation::fromRawData(
            ['findIfExists' => true, 'customSlug' => 'foo', 'longUrl' => $url],
        ), ShortUrl::withLongUrl($url)];
        yield [
            ShortUrlCreation::fromRawData(['findIfExists' => true, 'longUrl' => $url, 'tags' => ['foo', 'bar']]),
            ShortUrl::fromMeta(ShortUrlCreation::fromRawData(['longUrl' => $url, 'tags' => ['foo', 'bar']])),
        ];
        yield [
            ShortUrlCreation::fromRawData(['findIfExists' => true, 'maxVisits' => 3, 'longUrl' => $url]),
            ShortUrl::fromMeta(ShortUrlCreation::fromRawData(['maxVisits' => 3, 'longUrl' => $url])),
        ];
        yield [
            ShortUrlCreation::fromRawData(
                ['findIfExists' => true, 'validSince' => Chronos::parse('2017-01-01'), 'longUrl' => $url],
            ),
            ShortUrl::fromMeta(
                ShortUrlCreation::fromRawData(['validSince' => Chronos::parse('2017-01-01'), 'longUrl' => $url]),
            ),
        ];
        yield [
            ShortUrlCreation::fromRawData(
                ['findIfExists' => true, 'validUntil' => Chronos::parse('2017-01-01'), 'longUrl' => $url],
            ),
            ShortUrl::fromMeta(
                ShortUrlCreation::fromRawData(['validUntil' => Chronos::parse('2017-01-01'), 'longUrl' => $url]),
            ),
        ];
        yield [
            ShortUrlCreation::fromRawData(['findIfExists' => true, 'domain' => 'example.com', 'longUrl' => $url]),
            ShortUrl::fromMeta(ShortUrlCreation::fromRawData(['domain' => 'example.com', 'longUrl' => $url])),
        ];
        yield [
            ShortUrlCreation::fromRawData([
                'findIfExists' => true,
                'validUntil' => Chronos::parse('2017-01-01'),
                'maxVisits' => 4,
                'longUrl' => $url,
                'tags' => ['baz', 'foo', 'bar'],
            ]),
            ShortUrl::fromMeta(ShortUrlCreation::fromRawData([
                'validUntil' => Chronos::parse('2017-01-01'),
                'maxVisits' => 4,
                'longUrl' => $url,
                'tags' => ['foo', 'bar', 'baz'],
            ])),
        ];
    }
}
