<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Helper;

use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Config\Options\TrackingOptions;
use Shlinkio\Shlink\Core\RedirectRule\ShortUrlRedirectionResolverInterface;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlRedirectionBuilder;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;

class ShortUrlRedirectionBuilderTest extends TestCase
{
    private ShortUrlRedirectionBuilder $redirectionBuilder;
    private ShortUrlRedirectionResolverInterface & MockObject $redirectionResolver;

    protected function setUp(): void
    {
        $trackingOptions = new TrackingOptions(disableTrackParam: 'foobar');
        $this->redirectionResolver = $this->createMock(ShortUrlRedirectionResolverInterface::class);

        $this->redirectionBuilder = new ShortUrlRedirectionBuilder($trackingOptions, $this->redirectionResolver);
    }

    #[Test, DataProvider('provideData')]
    public function buildShortUrlRedirectBuildsExpectedUrl(
        string $expectedUrl,
        ServerRequestInterface $request,
        string|null $extraPath,
        bool|null $forwardQuery,
    ): void {
        $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'https://example.com/foo/bar?some=thing',
            'forwardQuery' => $forwardQuery,
        ]));
        $this->redirectionResolver->expects($this->once())->method('resolveLongUrl')->with(
            $shortUrl,
            $request,
        )->willReturn($shortUrl->getLongUrl());

        $result = $this->redirectionBuilder->buildShortUrlRedirect($shortUrl, $request, $extraPath);

        self::assertEquals($expectedUrl, $result);
    }

    public static function provideData(): iterable
    {
        $request = static fn (string $query = '') => ServerRequestFactory::fromGlobals()->withUri(
            (new Uri())->withQuery($query),
        );

        yield ['https://example.com/foo/bar?some=thing', $request(), null, true];
        yield ['https://example.com/foo/bar?some=thing', $request(), null, null];
        yield ['https://example.com/foo/bar?some=thing', $request(), null, false];
        yield ['https://example.com/foo/bar?some=thing&else', $request('else'), null, true];
        yield ['https://example.com/foo/bar?some=thing&foo=bar', $request('foo=bar'), null, true];
        yield ['https://example.com/foo/bar?some=thing&foo=bar', $request('foo=bar'), null, null];
        yield ['https://example.com/foo/bar?some=thing', $request('foo=bar'), null, false];
        yield ['https://example.com/foo/bar?some=thing&123=foo', $request('123=foo'), null, true];
        yield ['https://example.com/foo/bar?some=thing&456=foo', $request('456=foo'), null, true];
        yield ['https://example.com/foo/bar?some=thing&456=foo', $request('456=foo'), null, null];
        yield ['https://example.com/foo/bar?some=thing', $request('456=foo'), null, false];
        yield [
            'https://example.com/foo/bar?some=overwritten&foo=bar',
            $request('foo=bar&some=overwritten'),
            null,
            true,
        ];
        yield [
            'https://example.com/foo/bar?some=overwritten',
            $request('foobar=notrack&some=overwritten'),
            null,
            true,
        ];
        yield [
            'https://example.com/foo/bar?some=overwritten',
            $request('foobar=notrack&some=overwritten'),
            null,
            null,
        ];
        yield [
            'https://example.com/foo/bar?some=thing',
            $request('foobar=notrack&some=overwritten'),
            null,
            false,
        ];
        yield ['https://example.com/foo/bar/something/else-baz?some=thing', $request(), '/something/else-baz', true];
        yield [
            'https://example.com/foo/bar/something/else-baz?some=thing&hello=world',
            $request('hello=world',),
            '/something/else-baz',
            true,
        ];
        yield [
            'https://example.com/foo/bar/something/else-baz?some=thing&hello=world',
            $request('hello=world',),
            '/something/else-baz',
            null,
        ];
        yield [
            'https://example.com/foo/bar/something/else-baz?some=thing',
            $request('hello=world',),
            '/something/else-baz',
            false,
        ];
        yield [
            'https://example.com/foo/bar/something/else-baz?some=thing&parameter%20with%20spaces=world',
            $request('parameter with spaces=world',),
            '/something/else-baz',
            true,
        ];
    }

    /**
     * @param non-empty-string $longUrl
     */
    #[Test]
    #[TestWith(['android://foo/bar'])]
    #[TestWith(['fb://profile/33138223345'])]
    #[TestWith(['viber://pa?chatURI=1234'])]
    public function buildShortUrlRedirectBuildsNonHttpUrls(string $longUrl): void
    {
        $shortUrl = ShortUrl::withLongUrl($longUrl);
        $request = ServerRequestFactory::fromGlobals();

        $this->redirectionResolver->expects($this->once())->method('resolveLongUrl')->with(
            $shortUrl,
            $request,
        )->willReturn($shortUrl->getLongUrl());

        $result = $this->redirectionBuilder->buildShortUrlRedirect($shortUrl, $request);

        self::assertEquals($longUrl, $result);
    }
}
