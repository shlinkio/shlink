<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Visit\Entity;

use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Model\VisitType;
use Shlinkio\Shlink\IpGeolocation\Model\Location;

class VisitTest extends TestCase
{
    #[Test, DataProvider('provideUserAgents')]
    public function isProperlyJsonSerialized(string $userAgent, bool $expectedToBePotentialBot): void
    {
        $visit = Visit::forValidShortUrl(ShortUrl::createFake(), new Visitor($userAgent, 'some site', '1.2.3.4', ''));

        self::assertEquals([
            'referer' => 'some site',
            'date' => $visit->getDate()->toAtomString(),
            'userAgent' => $userAgent,
            'visitLocation' => null,
            'potentialBot' => $expectedToBePotentialBot,
        ], $visit->jsonSerialize());
    }

    public static function provideUserAgents(): iterable
    {
        yield 'Chrome' => [
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.88 Safari/537.36',
            false,
        ];
        yield 'Firefox' => ['Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:47.0) Gecko/20100101 Firefox/47.0', false];
        yield 'Facebook' => ['cf-facebook', true];
        yield 'Twitter' => ['IDG Twitter Links Resolver', true];
        yield 'Guzzle' => ['guzzlehttp', true];
    }

    #[Test, DataProvider('provideOrphanVisits')]
    public function isProperlyJsonSerializedWhenOrphan(Visit $visit, array $expectedResult): void
    {
        self::assertEquals($expectedResult, $visit->jsonSerialize());
    }

    public static function provideOrphanVisits(): iterable
    {
        yield 'base path visit' => [
            $visit = Visit::forBasePath(Visitor::emptyInstance()),
            [
                'referer' => '',
                'date' => $visit->getDate()->toAtomString(),
                'userAgent' => '',
                'visitLocation' => null,
                'potentialBot' => false,
                'visitedUrl' => '',
                'type' => VisitType::BASE_URL->value,
            ],
        ];
        yield 'invalid short url visit' => [
            $visit = Visit::forInvalidShortUrl(Visitor::fromRequest(
                ServerRequestFactory::fromGlobals()->withHeader('User-Agent', 'foo')
                    ->withHeader('Referer', 'bar')
                    ->withUri(new Uri('https://example.com/foo')),
            )),
            [
                'referer' => 'bar',
                'date' => $visit->getDate()->toAtomString(),
                'userAgent' => 'foo',
                'visitLocation' => null,
                'potentialBot' => false,
                'visitedUrl' => 'https://example.com/foo',
                'type' => VisitType::INVALID_SHORT_URL->value,
            ],
        ];
        yield 'regular 404 visit' => [
            $visit = Visit::forRegularNotFound(
                Visitor::fromRequest(
                    ServerRequestFactory::fromGlobals()->withHeader('User-Agent', 'user-agent')
                        ->withHeader('Referer', 'referer')
                        ->withUri(new Uri('https://s.test/foo/bar')),
                ),
            )->locate($location = VisitLocation::fromGeolocation(Location::emptyInstance())),
            [
                'referer' => 'referer',
                'date' => $visit->getDate()->toAtomString(),
                'userAgent' => 'user-agent',
                'visitLocation' => $location,
                'potentialBot' => false,
                'visitedUrl' => 'https://s.test/foo/bar',
                'type' => VisitType::REGULAR_404->value,
            ],
        ];
    }

    #[Test, DataProvider('provideAddresses')]
    public function addressIsAnonymizedWhenRequested(bool $anonymize, ?string $address, ?string $expectedAddress): void
    {
        $visit = Visit::forValidShortUrl(
            ShortUrl::createFake(),
            new Visitor('Chrome', 'some site', $address, ''),
            $anonymize,
        );

        self::assertEquals($expectedAddress, $visit->remoteAddr);
    }

    public static function provideAddresses(): iterable
    {
        yield 'anonymized null address' => [true, null, null];
        yield 'non-anonymized null address' => [false, null, null];
        yield 'anonymized localhost' => [true, IpAddress::LOCALHOST, IpAddress::LOCALHOST];
        yield 'non-anonymized localhost' => [false, IpAddress::LOCALHOST, IpAddress::LOCALHOST];
        yield 'anonymized regular address' => [true, '1.2.3.4', '1.2.3.0'];
        yield 'non-anonymized regular address' => [false, '1.2.3.4', '1.2.3.4'];
    }
}
