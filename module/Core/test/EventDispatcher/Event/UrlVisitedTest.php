<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher\Event;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\EventDispatcher\Event\UrlVisited;

class UrlVisitedTest extends TestCase
{
    #[Test]
    public function jsonSerialization(): void
    {
        $visitId = 'abc123';
        self::assertEquals(
            ['visitId' => $visitId, 'originalIpAddress' => null],
            new UrlVisited($visitId)->jsonSerialize(),
        );
    }

    #[Test]
    #[TestWith([['visitId' => '123', 'originalIpAddress' => '1.2.3.4'], '123', '1.2.3.4'])]
    #[TestWith([['visitId' => '123'], '123', null])]
    #[TestWith([['originalIpAddress' => '1.2.3.4'], '', '1.2.3.4'])]
    #[TestWith([[], '', null])]
    public function creationFromPayload(array $payload, string $expectedVisitId, string|null $expectedIpAddress): void
    {
        $event = UrlVisited::fromPayload($payload);
        self::assertEquals($expectedVisitId, $event->visitId);
        self::assertEquals($expectedIpAddress, $event->originalIpAddress);
    }
}
