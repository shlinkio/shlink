<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Mercure;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Mercure\MercureUpdatesGenerator;
use Shlinkio\Shlink\Core\Model\Visitor;

use function Shlinkio\Shlink\Common\json_decode;

class MercureUpdatesGeneratorTest extends TestCase
{
    private MercureUpdatesGenerator $generator;

    public function setUp(): void
    {
        $this->generator = new MercureUpdatesGenerator([]);
    }

    /** @test */
    public function visitIsProperlySerializedIntoUpdate(): void
    {
        $shortUrl = new ShortUrl('');
        $visit = new Visit($shortUrl, Visitor::emptyInstance());

        $update = $this->generator->newVisitUpdate($visit);

        $this->assertEquals(['https://shlink.io/new_visit'], $update->getTopics());
        $this->assertEquals([
            'shortUrl' => [
                'shortCode' => $shortUrl->getShortCode(),
                'shortUrl' => 'http:/' . $shortUrl->getShortCode(),
                'longUrl' => '',
                'dateCreated' => $shortUrl->getDateCreated()->toAtomString(),
                'visitsCount' => 0,
                'tags' => [],
                'meta' => [
                    'validSince' => null,
                    'validUntil' => null,
                    'maxVisits' => null,
                ],
                'domain' => null,
            ],
            'visit' => [
                'referer' => '',
                'userAgent' => '',
                'visitLocation' => null,
                'date' => $visit->getDate()->toAtomString(),
            ],
        ], json_decode($update->getData()));
    }
}
