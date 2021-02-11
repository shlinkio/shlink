<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Entity;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Util\IpAddress;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Model\Visitor;

class VisitTest extends TestCase
{
    /** @test */
    public function isProperlyJsonSerialized(): void
    {
        $visit = Visit::forValidShortUrl(ShortUrl::createEmpty(), new Visitor('Chrome', 'some site', '1.2.3.4', ''));

        self::assertEquals([
            'referer' => 'some site',
            'date' => $visit->getDate()->toAtomString(),
            'userAgent' => 'Chrome',
            'visitLocation' => null,
        ], $visit->jsonSerialize());
    }

    /**
     * @test
     * @dataProvider provideAddresses
     */
    public function addressIsAnonymizedWhenRequested(bool $anonymize, ?string $address, ?string $expectedAddress): void
    {
        $visit = Visit::forValidShortUrl(
            ShortUrl::createEmpty(),
            new Visitor('Chrome', 'some site', $address, ''),
            $anonymize,
        );

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
