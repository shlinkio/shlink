<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Exception;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;

class InvalidShortCodeExceptionTest extends TestCase
{
    /** @test */
    public function properlyCreatesExceptionFromNotFoundShortCode(): void
    {
        $e = InvalidShortCodeException::fromNotFoundShortCode('abc123');

        $this->assertEquals('No URL found for short code "abc123"', $e->getMessage());
    }
}
