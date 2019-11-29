<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Exception;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;

class ShortUrlNotFoundExceptionTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideMessages
     */
    public function properlyCreatesExceptionFromNotFoundShortCode(
        string $expectedMessage,
        string $shortCode,
        ?string $domain
    ): void {
        $e = ShortUrlNotFoundException::fromNotFoundShortCode($shortCode, $domain);
        $this->assertEquals($expectedMessage, $e->getMessage());
    }

    public function provideMessages(): iterable
    {
        yield 'without domain' => [
            'No URL found with short code "abc123"',
            'abc123',
            null,
        ];
        yield 'with domain' => [
            'No URL found with short code "bar" for domain "foo"',
            'bar',
            'foo',
        ];
    }
}
