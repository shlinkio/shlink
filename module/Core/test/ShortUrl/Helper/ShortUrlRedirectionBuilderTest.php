<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Helper;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Options\TrackingOptions;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlRedirectionBuilder;

class ShortUrlRedirectionBuilderTest extends TestCase
{
    private ShortUrlRedirectionBuilder $redirectionBuilder;
    private TrackingOptions $trackingOptions;

    protected function setUp(): void
    {
        $this->trackingOptions = new TrackingOptions(['disable_track_param' => 'foobar']);
        $this->redirectionBuilder = new ShortUrlRedirectionBuilder($this->trackingOptions);
    }

    /**
     * @test
     * @dataProvider provideData
     */
    public function buildShortUrlRedirectBuildsExpectedUrl(string $expectedUrl, array $query, ?string $extraPath): void
    {
        $shortUrl = ShortUrl::withLongUrl('https://domain.com/foo/bar?some=thing');
        $result = $this->redirectionBuilder->buildShortUrlRedirect($shortUrl, $query, $extraPath);

        self::assertEquals($expectedUrl, $result);
    }

    public function provideData(): iterable
    {
        yield ['https://domain.com/foo/bar?some=thing', [], null];
        yield ['https://domain.com/foo/bar?some=thing&else', ['else' => null], null];
        yield ['https://domain.com/foo/bar?some=thing&foo=bar', ['foo' => 'bar'], null];
        yield ['https://domain.com/foo/bar?some=thing&123=foo', ['123' => 'foo'], null];
        yield ['https://domain.com/foo/bar?some=thing&456=foo', [456 => 'foo'], null];
        yield ['https://domain.com/foo/bar?some=overwritten&foo=bar', ['foo' => 'bar', 'some' => 'overwritten'], null];
        yield ['https://domain.com/foo/bar?some=overwritten', ['foobar' => 'notrack', 'some' => 'overwritten'], null];
        yield ['https://domain.com/foo/bar/something/else-baz?some=thing', [], '/something/else-baz'];
        yield [
            'https://domain.com/foo/bar/something/else-baz?some=thing&hello=world',
            ['hello' => 'world'],
            '/something/else-baz',
        ];
    }
}
