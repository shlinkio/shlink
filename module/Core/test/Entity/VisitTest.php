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

        $this->assertEquals([
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
    public function addressIsObfuscatedWhenRequested(bool $obfuscate, ?string $address, ?string $expectedAddress): void
    {
        $visit = new Visit(new ShortUrl(''), new Visitor('Chrome', 'some site', $address), $obfuscate);

        $this->assertEquals($expectedAddress, $visit->getRemoteAddr());
    }

    public function provideAddresses(): iterable
    {
        yield 'obfuscated null address' => [true, null, null];
        yield 'non-obfuscated null address' => [false, null, null];
        yield 'obfuscated localhost' => [true, IpAddress::LOCALHOST, IpAddress::LOCALHOST];
        yield 'non-obfuscated localhost' => [false, IpAddress::LOCALHOST, IpAddress::LOCALHOST];
        yield 'obfuscated regular address' => [true, '1.2.3.4', '1.2.3.0'];
        yield 'non-obfuscated regular address' => [false, '1.2.3.4', '1.2.3.4'];
    }
}
