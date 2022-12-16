<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Helper;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Options\TrackingOptions;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlRedirectionBuilder;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;

class ShortUrlRedirectionBuilderTest extends TestCase
{
    private ShortUrlRedirectionBuilder $redirectionBuilder;

    protected function setUp(): void
    {
        $trackingOptions = new TrackingOptions(disableTrackParam: 'foobar');
        $this->redirectionBuilder = new ShortUrlRedirectionBuilder($trackingOptions);
    }

    /**
     * @test
     * @dataProvider provideData
     */
    public function buildShortUrlRedirectBuildsExpectedUrl(
        string $expectedUrl,
        array $query,
        ?string $extraPath,
        ?bool $forwardQuery,
    ): void {
        $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'https://domain.com/foo/bar?some=thing',
            'forwardQuery' => $forwardQuery,
        ]));
        $result = $this->redirectionBuilder->buildShortUrlRedirect($shortUrl, $query, $extraPath);

        self::assertEquals($expectedUrl, $result);
    }

    public function provideData(): iterable
    {
        yield ['https://domain.com/foo/bar?some=thing', [], null, true];
        yield ['https://domain.com/foo/bar?some=thing', [], null, null];
        yield ['https://domain.com/foo/bar?some=thing', [], null, false];
        yield ['https://domain.com/foo/bar?some=thing&else', ['else' => null], null, true];
        yield ['https://domain.com/foo/bar?some=thing&foo=bar', ['foo' => 'bar'], null, true];
        yield ['https://domain.com/foo/bar?some=thing&foo=bar', ['foo' => 'bar'], null, null];
        yield ['https://domain.com/foo/bar?some=thing', ['foo' => 'bar'], null, false];
        yield ['https://domain.com/foo/bar?some=thing&123=foo', ['123' => 'foo'], null, true];
        yield ['https://domain.com/foo/bar?some=thing&456=foo', [456 => 'foo'], null, true];
        yield ['https://domain.com/foo/bar?some=thing&456=foo', [456 => 'foo'], null, null];
        yield ['https://domain.com/foo/bar?some=thing', [456 => 'foo'], null, false];
        yield [
            'https://domain.com/foo/bar?some=overwritten&foo=bar',
            ['foo' => 'bar', 'some' => 'overwritten'],
            null,
            true,
        ];
        yield [
            'https://domain.com/foo/bar?some=overwritten',
            ['foobar' => 'notrack', 'some' => 'overwritten'],
            null,
            true,
        ];
        yield [
            'https://domain.com/foo/bar?some=overwritten',
            ['foobar' => 'notrack', 'some' => 'overwritten'],
            null,
            null,
        ];
        yield [
            'https://domain.com/foo/bar?some=thing',
            ['foobar' => 'notrack', 'some' => 'overwritten'],
            null,
            false,
        ];
        yield ['https://domain.com/foo/bar/something/else-baz?some=thing', [], '/something/else-baz', true];
        yield [
            'https://domain.com/foo/bar/something/else-baz?some=thing&hello=world',
            ['hello' => 'world'],
            '/something/else-baz',
            true,
        ];
        yield [
            'https://domain.com/foo/bar/something/else-baz?some=thing&hello=world',
            ['hello' => 'world'],
            '/something/else-baz',
            null,
        ];
        yield [
            'https://domain.com/foo/bar/something/else-baz?some=thing',
            ['hello' => 'world'],
            '/something/else-baz',
            false,
        ];
    }
}
