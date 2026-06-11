<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Model;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlMode;

class ShortUrlCreationTest extends TestCase
{
    #[Test, DataProvider('provideCustomSlugs')]
    public function properlyCreatesInstancesWithCustomSlug(
        string $customSlug,
        string $expectedSlug,
        bool $multiSegmentEnabled = false,
        ShortUrlMode $shortUrlMode = ShortUrlMode::STRICT,
    ): void {
        $creation = new ShortUrlCreation(
            longUrl: 'https://longUrl',
            shortUrlMode: $shortUrlMode,
            multiSegmentSlugsEnabled: $multiSegmentEnabled,
            customSlug: $customSlug,
        );

        self::assertTrue($creation->hasCustomSlug());
        self::assertEquals($expectedSlug, $creation->customSlug);
    }

    public static function provideCustomSlugs(): iterable
    {
        yield ['🔥', '🔥'];
        yield ['🦣 🍅', '🦣-🍅'];
        yield ['🦣 🍅', '🦣-🍅', false, ShortUrlMode::LOOSE];
        yield ['foobar', 'foobar'];
        yield ['foo bar', 'foo-bar'];
        yield ['foo bar baz', 'foo-bar-baz'];
        yield ['foo bar-baz', 'foo-bar-baz'];
        yield ['foo BAR-baz', 'foo-bar-baz', false, ShortUrlMode::LOOSE];
        yield ['foo/bar/baz', 'foo/bar/baz', true];
        yield ['/foo/bar/baz', 'foo/bar/baz', true];
        yield ['/foo/baR/baZ', 'foo/bar/baz', true, ShortUrlMode::LOOSE];
        yield ['foo/bar/baz', 'foo-bar-baz'];
        yield ['/foo/bar/baz', '-foo-bar-baz'];
        yield ['wp-admin.php', 'wp-admin.php'];
        yield ['UPPER_lower', 'UPPER_lower'];
        yield ['UPPER_lower', 'upper_lower', false, ShortUrlMode::LOOSE];
        yield ['more~url_special.chars', 'more~url_special.chars'];
        yield ['구글', '구글'];
        yield ['グーグル', 'グーグル'];
        yield ['谷歌', '谷歌'];
        yield ['гугл', 'гугл'];
    }
}
