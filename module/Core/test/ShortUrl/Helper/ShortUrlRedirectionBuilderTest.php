<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Helper;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Options\TrackingOptions;
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
        ?string $extraPath,
        ?bool $forwardQuery,
    ): void {
        $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'https://domain.com/foo/bar?some=thing',
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
        $request = static fn (array $query = []) => ServerRequestFactory::fromGlobals()->withQueryParams($query);

        yield ['https://domain.com/foo/bar?some=thing', $request(), null, true];
        yield ['https://domain.com/foo/bar?some=thing', $request(), null, null];
        yield ['https://domain.com/foo/bar?some=thing', $request(), null, false];
        yield ['https://domain.com/foo/bar?some=thing&else', $request(['else' => null]), null, true];
        yield ['https://domain.com/foo/bar?some=thing&foo=bar', $request(['foo' => 'bar']), null, true];
        yield ['https://domain.com/foo/bar?some=thing&foo=bar', $request(['foo' => 'bar']), null, null];
        yield ['https://domain.com/foo/bar?some=thing', $request(['foo' => 'bar']), null, false];
        yield ['https://domain.com/foo/bar?some=thing&123=foo', $request(['123' => 'foo']), null, true];
        yield ['https://domain.com/foo/bar?some=thing&456=foo', $request([456 => 'foo']), null, true];
        yield ['https://domain.com/foo/bar?some=thing&456=foo', $request([456 => 'foo']), null, null];
        yield ['https://domain.com/foo/bar?some=thing', $request([456 => 'foo']), null, false];
        yield [
            'https://domain.com/foo/bar?some=overwritten&foo=bar',
            $request(['foo' => 'bar', 'some' => 'overwritten']),
            null,
            true,
        ];
        yield [
            'https://domain.com/foo/bar?some=overwritten',
            $request(['foobar' => 'notrack', 'some' => 'overwritten']),
            null,
            true,
        ];
        yield [
            'https://domain.com/foo/bar?some=overwritten',
            $request(['foobar' => 'notrack', 'some' => 'overwritten']),
            null,
            null,
        ];
        yield [
            'https://domain.com/foo/bar?some=thing',
            $request(['foobar' => 'notrack', 'some' => 'overwritten']),
            null,
            false,
        ];
        yield ['https://domain.com/foo/bar/something/else-baz?some=thing', $request(), '/something/else-baz', true];
        yield [
            'https://domain.com/foo/bar/something/else-baz?some=thing&hello=world',
            $request(['hello' => 'world']),
            '/something/else-baz',
            true,
        ];
        yield [
            'https://domain.com/foo/bar/something/else-baz?some=thing&hello=world',
            $request(['hello' => 'world']),
            '/something/else-baz',
            null,
        ];
        yield [
            'https://domain.com/foo/bar/something/else-baz?some=thing',
            $request(['hello' => 'world']),
            '/something/else-baz',
            false,
        ];
    }
}
