<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Visit\Transformer;

use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Model\VisitType;
use Shlinkio\Shlink\Core\Visit\Transformer\OrphanVisitDataTransformer;
use Shlinkio\Shlink\IpGeolocation\Model\Location;

class OrphanVisitDataTransformerTest extends TestCase
{
    private OrphanVisitDataTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new OrphanVisitDataTransformer();
    }

    /**
     * @test
     * @dataProvider provideVisits
     */
    public function visitsAreParsedAsExpected(Visit $visit, array $expectedResult): void
    {
        $result = $this->transformer->transform($visit);

        self::assertEquals($expectedResult, $result);
    }

    public function provideVisits(): iterable
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
                                                       ->withUri(new Uri('https://doma.in/foo/bar')),
                ),
            )->locate($location = VisitLocation::fromGeolocation(Location::emptyInstance())),
            [
                'referer' => 'referer',
                'date' => $visit->getDate()->toAtomString(),
                'userAgent' => 'user-agent',
                'visitLocation' => $location,
                'potentialBot' => false,
                'visitedUrl' => 'https://doma.in/foo/bar',
                'type' => VisitType::REGULAR_404->value,
            ],
        ];
    }
}
