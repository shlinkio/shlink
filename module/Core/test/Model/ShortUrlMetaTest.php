<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Model;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Validation\ShortUrlInputFilter;
use stdClass;

use function str_pad;

use const STR_PAD_BOTH;

class ShortUrlMetaTest extends TestCase
{
    /**
     * @param array $data
     * @test
     * @dataProvider provideInvalidData
     */
    public function exceptionIsThrownIfProvidedDataIsInvalid(array $data): void
    {
        $this->expectException(ValidationException::class);
        ShortUrlMeta::fromRawData($data);
    }

    public function provideInvalidData(): iterable
    {
        yield [[]];
        yield [[
            ShortUrlInputFilter::LONG_URL => 'foo',
            ShortUrlInputFilter::VALID_SINCE => '',
            ShortUrlInputFilter::VALID_UNTIL => '',
            ShortUrlInputFilter::CUSTOM_SLUG => 'foobar',
            ShortUrlInputFilter::MAX_VISITS => 'invalid',
        ]];
        yield [[
            ShortUrlInputFilter::LONG_URL => 'foo',
            ShortUrlInputFilter::VALID_SINCE => '2017',
            ShortUrlInputFilter::MAX_VISITS => 5,
        ]];
        yield [[
            ShortUrlInputFilter::LONG_URL => 'foo',
            ShortUrlInputFilter::VALID_SINCE => new stdClass(),
            ShortUrlInputFilter::VALID_UNTIL => 'foo',
        ]];
        yield [[
            ShortUrlInputFilter::LONG_URL => 'foo',
            ShortUrlInputFilter::VALID_UNTIL => 500,
            ShortUrlInputFilter::DOMAIN => 4,
        ]];
        yield [[
            ShortUrlInputFilter::LONG_URL => 'foo',
            ShortUrlInputFilter::SHORT_CODE_LENGTH => 3,
        ]];
        yield [[
            ShortUrlInputFilter::LONG_URL => 'foo',
            ShortUrlInputFilter::CUSTOM_SLUG => '',
        ]];
        yield [[
            ShortUrlInputFilter::LONG_URL => 'foo',
            ShortUrlInputFilter::CUSTOM_SLUG => '   ',
        ]];
        yield [[
            ShortUrlInputFilter::LONG_URL => [],
        ]];
    }

    /**
     * @test
     * @dataProvider provideCustomSlugs
     */
    public function properlyCreatedInstanceReturnsValues(string $customSlug, string $expectedSlug): void
    {
        $meta = ShortUrlMeta::fromRawData([
            'validSince' => Chronos::parse('2015-01-01')->toAtomString(),
            'customSlug' => $customSlug,
            'longUrl' => '',
        ]);

        self::assertTrue($meta->hasValidSince());
        self::assertEquals(Chronos::parse('2015-01-01'), $meta->getValidSince());

        self::assertFalse($meta->hasValidUntil());
        self::assertNull($meta->getValidUntil());

        self::assertTrue($meta->hasCustomSlug());
        self::assertEquals($expectedSlug, $meta->getCustomSlug());

        self::assertFalse($meta->hasMaxVisits());
        self::assertNull($meta->getMaxVisits());
    }

    public function provideCustomSlugs(): iterable
    {
        yield ['🔥', '🔥'];
        yield ['🦣 🍅', '🦣-🍅'];
        yield ['foobar', 'foobar'];
        yield ['foo bar', 'foo-bar'];
        yield ['foo bar baz', 'foo-bar-baz'];
        yield ['foo bar-baz', 'foo-bar-baz'];
        yield ['foo/bar/baz', 'foo-bar-baz'];
        yield ['wp-admin.php', 'wp-admin.php'];
        yield ['UPPER_lower', 'UPPER_lower'];
        yield ['more~url_special.chars', 'more~url_special.chars'];
        yield ['구글', '구글'];
        yield ['グーグル', 'グーグル'];
        yield ['谷歌', '谷歌'];
        yield ['гугл', 'гугл'];
    }

    /**
     * @test
     * @dataProvider provideTitles
     */
    public function titleIsCroppedIfTooLong(?string $title, ?string $expectedTitle): void
    {
        $meta = ShortUrlMeta::fromRawData([
            'title' => $title,
            'longUrl' => '',
        ]);

        self::assertEquals($expectedTitle, $meta->getTitle());
    }

    public function provideTitles(): iterable
    {
        yield [null, null];
        yield ['foo', 'foo'];
        yield [str_pad('bar', 600, ' ', STR_PAD_BOTH), 'bar'];
        yield [str_pad('', 511, 'a'), str_pad('', 511, 'a')];
        yield [str_pad('', 512, 'b'), str_pad('', 512, 'b')];
        yield [str_pad('', 513, 'c'), str_pad('', 512, 'c')];
        yield [str_pad('', 600, 'd'), str_pad('', 512, 'd')];
        yield [str_pad('', 800, 'e'), str_pad('', 512, 'e')];
    }
}
