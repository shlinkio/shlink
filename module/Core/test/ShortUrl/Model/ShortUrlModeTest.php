<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Model;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlMode;

class ShortUrlModeTest extends TestCase
{
    #[Test, DataProvider('provideModes')]
    public function deprecatedValuesAreProperlyParsed(string $mode, ?ShortUrlMode $expected): void
    {
        self::assertSame($expected, ShortUrlMode::tryDeprecated($mode));
    }

    public static function provideModes(): iterable
    {
        yield 'invalid' => ['invalid', null];
        yield 'foo' => ['foo', null];
        yield 'loose' => ['loose', ShortUrlMode::LOOSE];
        yield 'loosely' => ['loosely', ShortUrlMode::LOOSE];
        yield 'strict' => ['strict', ShortUrlMode::STRICT];
    }
}
