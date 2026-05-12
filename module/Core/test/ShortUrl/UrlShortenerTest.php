<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl;

use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManagerInterface;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortCodeUniquenessHelperInterface;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlTitleResolutionHelperInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\SimpleShortUrlRelationResolver;
use Shlinkio\Shlink\Core\ShortUrl\UrlShortener;

#[AllowMockObjectsWithoutExpectations]
class UrlShortenerTest extends TestCase
{
    private UrlShortener $urlShortener;
    private MockObject & ShortUrlTitleResolutionHelperInterface $titleResolutionHelper;
    private MockObject & ShortCodeUniquenessHelperInterface $shortCodeHelper;
    private MockObject & EventDispatcherInterface $dispatcher;
    private MockObject & ShortUrlRepositoryInterface $repo;

    protected function setUp(): void
    {
        $this->titleResolutionHelper = $this->createMock(ShortUrlTitleResolutionHelperInterface::class);
        $this->shortCodeHelper = $this->createMock(ShortCodeUniquenessHelperInterface::class);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(fn (ShortUrl $shortUrl) => $shortUrl->setId('10'));
        $em->method('wrapInTransaction')->willReturnCallback(fn (callable $callback) => $callback());

        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->repo = $this->createMock(ShortUrlRepositoryInterface::class);

        $this->urlShortener = new UrlShortener(
            $this->titleResolutionHelper,
            $em,
            new SimpleShortUrlRelationResolver(),
            $this->shortCodeHelper,
            $this->dispatcher,
            $this->repo,
        );
    }

    #[Test, DataProvider('provideDispatchBehavior')]
    public function urlIsProperlyShortened(bool $expectDispatchError, callable $dispatchBehavior): void
    {
        $longUrl = 'http://foobar.com/12345/hello?foo=bar';
        $meta = new ShortUrlCreation($longUrl);
        $this->titleResolutionHelper->expects($this->once())->method('processTitle')->with(
            $meta,
        )->willReturnArgument(0);
        $this->shortCodeHelper->method('ensureShortCodeUniqueness')->willReturn(true);
        $this->dispatcher->expects($this->once())->method('dispatch')->willReturnCallback($dispatchBehavior);

        $result = $this->urlShortener->shorten($meta);
        $thereIsError = false;
        $result->onEventDispatchingError(function () use (&$thereIsError): void {
            $thereIsError = true;
        });

        self::assertEquals($longUrl, $result->shortUrl->getLongUrl());
        self::assertEquals($expectDispatchError, $thereIsError);
    }

    public static function provideDispatchBehavior(): iterable
    {
        yield 'no dispatch error' => [false, static function (): void {
        }];
        yield 'dispatch error' => [true, static function (): void {
            throw new ServiceNotFoundException();
        }];
    }

    #[Test]
    public function exceptionIsThrownWhenNonUniqueSlugIsProvided(): void
    {
        $meta = new ShortUrlCreation(longUrl: 'http://foobar.com/12345/hello?foo=bar', customSlug: 'custom-slug');

        $this->shortCodeHelper->expects($this->once())->method('ensureShortCodeUniqueness')->willReturn(false);
        $this->titleResolutionHelper->expects($this->once())->method('processTitle')->with(
            $meta,
        )->willReturnArgument(0);

        $this->expectException(NonUniqueSlugException::class);

        $this->urlShortener->shorten($meta);
    }

    #[Test, DataProvider('provideExistingShortUrls')]
    public function existingShortUrlIsReturnedWhenRequested(ShortUrlCreation $meta, ShortUrl $expected): void
    {
        $this->repo->expects($this->once())->method('findOneMatching')->willReturn($expected);
        $this->titleResolutionHelper->expects($this->never())->method('processTitle');
        $this->shortCodeHelper->method('ensureShortCodeUniqueness')->willReturn(true);

        $result = $this->urlShortener->shorten($meta);

        self::assertSame($expected, $result->shortUrl);
    }

    public static function provideExistingShortUrls(): iterable
    {
        $url = 'http://foo.com';

        yield [new ShortUrlCreation($url, findIfExists: true), ShortUrl::withLongUrl($url)];
        yield [new ShortUrlCreation($url, customSlug: 'foo', findIfExists: true), ShortUrl::withLongUrl($url)];
        yield [
            new ShortUrlCreation($url, findIfExists: true, tags: ['foo', 'bar']),
            ShortUrl::create(new ShortUrlCreation($url, tags: ['foo', 'bar'])),
        ];
        yield [
            new ShortUrlCreation($url, maxVisits: 3, findIfExists: true),
            ShortUrl::create(new ShortUrlCreation($url, maxVisits: 3)),
        ];
        yield [
            new ShortUrlCreation($url, validSince: Chronos::parse('2017-01-01'), findIfExists: true),
            ShortUrl::create(new ShortUrlCreation($url, validSince: Chronos::parse('2017-01-01'))),
        ];
        yield [
            new ShortUrlCreation($url, validUntil: Chronos::parse('2017-01-01'), findIfExists: true),
            ShortUrl::create(new ShortUrlCreation($url, validUntil: Chronos::parse('2017-01-01'))),
        ];
        yield [
            new ShortUrlCreation($url, findIfExists: true, domain: 'example.com'),
            ShortUrl::create(new ShortUrlCreation($url, domain: 'example.com')),
        ];
        yield [
            new ShortUrlCreation(
                longUrl: $url,
                validUntil: Chronos::parse('2017-01-01'),
                maxVisits: 4,
                findIfExists: true,
                tags: ['baz', 'foo', 'bar'],
            ),
            ShortUrl::create(new ShortUrlCreation(
                longUrl: $url,
                validUntil: Chronos::parse('2017-01-01'),
                maxVisits: 4,
                tags: ['foo', 'bar', 'baz'],
            )),
        ];
    }
}
