<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Entity;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Model\Visitor;

class VisitTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideDates
     */
    public function isProperlyJsonSerialized(?Chronos $date): void
    {
        $visit = new Visit(new ShortUrl(''), new Visitor('Chrome', 'some site', '1.2.3.4'), true, $date);

        self::assertEquals([
            'referer' => 'some site',
            'date' => ($date ?? $visit->getDate())->toAtomString(),
            'userAgent' => 'Chrome',
            'visitLocation' => null,
        ], $visit->jsonSerialize());
    }

    public function provideDates(): iterable
    {
        yield 'null date' => [null];
        yield 'not null date' => [Chronos::now()->subDays(10)];
    }

    /**
     * @test
     * @dataProvider provideAddresses
     */
    public function addressIsAnonymizedWhenRequested(bool $anonymize, ?string $address, ?string $expectedAddress): void
    {
        $visit = new Visit(new ShortUrl(''), new Visitor('Chrome', 'some site', $address), $anonymize);

        self::assertEquals($expectedAddress, $visit->getRemoteAddr());
    }

    public function provideAddresses(): iterable
    {
        yield 'anonymized null address' => [true, null, null];
        yield 'non-anonymized null address' => [false, null, null];
        yield 'anonymized localhost' => [true, IpAddress::LOCALHOST, IpAddress::LOCALHOST];
        yield 'non-anonymized localhost' => [false, IpAddress::LOCALHOST, IpAddress::LOCALHOST];
        yield 'anonymized regular address' => [true, '1.2.3.4', '1.2.3.0'];
        yield 'non-anonymized regular address' => [false, '1.2.3.4', '1.2.3.4'];
    }
}
