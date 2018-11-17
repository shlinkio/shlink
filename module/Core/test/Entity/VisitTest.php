<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Entity;

use Cake\Chronos\Chronos;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Model\Visitor;

class VisitTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideDates
     */
    public function isProperlyJsonSerialized(?Chronos $date)
    {
        $visit = new Visit(new ShortUrl(''), new Visitor('Chrome', 'some site', '1.2.3.4'), $date);

        $this->assertEquals([
            'referer' => 'some site',
            'date' => ($date ?? $visit->getDate())->toAtomString(),
            'userAgent' => 'Chrome',
            'visitLocation' => null,

            // Deprecated
            'remoteAddr' => null,
        ], $visit->jsonSerialize());
    }

    public function provideDates(): array
    {
        return [
            [null],
            [Chronos::now()->subDays(10)],
        ];
    }
}
