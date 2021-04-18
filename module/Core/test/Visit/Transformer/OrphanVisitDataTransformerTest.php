<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Visit\Transformer;

use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Model\Visitor;
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
                'visitedUrl' => '',
                'type' => Visit::TYPE_BASE_URL,
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
                'visitedUrl' => 'https://example.com/foo',
                'type' => Visit::TYPE_INVALID_SHORT_URL,
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
                'visitedUrl' => 'https://doma.in/foo/bar',
                'type' => Visit::TYPE_REGULAR_404,
            ],
        ];
    }
}
