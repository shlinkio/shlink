<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher\Event;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\EventDispatcher\Event\ShortUrlCreated;

class ShortUrlCreatedTest extends TestCase
{
    #[Test]
    public function jsonSerialization(): void
    {
        $shortUrlId = 'abc123';
        self::assertEquals(['shortUrlId' => $shortUrlId], new ShortUrlCreated($shortUrlId)->jsonSerialize());
    }

    #[Test]
    #[TestWith([['shortUrlId' => '123'], '123'])]
    #[TestWith([[], ''])]
    public function creationFromPayload(array $payload, string $expectedShortUrlId): void
    {
        $event = ShortUrlCreated::fromPayload($payload);
        self::assertEquals($expectedShortUrlId, $event->shortUrlId);
    }
}
