<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Helper;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\Options\TrackingOptions;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlRedirectionBuilder;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;

use const ShlinkioTest\Shlink\ANDROID_USER_AGENT;
use const ShlinkioTest\Shlink\DESKTOP_USER_AGENT;
use const ShlinkioTest\Shlink\IOS_USER_AGENT;

class ShortUrlRedirectionBuilderTest extends TestCase
{
    private ShortUrlRedirectionBuilder $redirectionBuilder;

    protected function setUp(): void
    {
        $trackingOptions = new TrackingOptions(disableTrackParam: 'foobar');
        $this->redirectionBuilder = new ShortUrlRedirectionBuilder($trackingOptions);
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
            'deviceLongUrls' => [
                DeviceType::ANDROID->value => 'https://domain.com/android',
                DeviceType::IOS->value => 'https://domain.com/ios',
            ],
        ]));
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
            $request(['foobar' => 'notrack', 'some' => 'overwritten'])->withHeader('User-Agent', 'Unknown'),
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
            $request(['hello' => 'world'])->withHeader('User-Agent', DESKTOP_USER_AGENT),
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
        yield [
            'https://domain.com/android/something',
            $request(['foo' => 'bar'])->withHeader('User-Agent', ANDROID_USER_AGENT),
            '/something',
            false,
        ];
        yield [
            'https://domain.com/ios?foo=bar',
            $request(['foo' => 'bar'])->withHeader('User-Agent', IOS_USER_AGENT),
            null,
            null,
        ];
    }
}
