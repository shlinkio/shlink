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
    /**
     * @test
     * @dataProvider provideUserAgents
     */
    public function isProperlyJsonSerialized(string $userAgent, bool $expectedToBePotentialBot): void
    {
        $visit = Visit::forValidShortUrl(ShortUrl::createEmpty(), new Visitor($userAgent, 'some site', '1.2.3.4', ''));

        self::assertEquals([
            'referer' => 'some site',
            'date' => $visit->getDate()->toAtomString(),
            'userAgent' => $userAgent,
            'visitLocation' => null,
            'potentialBot' => $expectedToBePotentialBot,
        ], $visit->jsonSerialize());
    }

    public function provideUserAgents(): iterable
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
