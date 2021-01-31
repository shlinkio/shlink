<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Model;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Validation\ShortUrlInputFilter;
use stdClass;

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
        yield [[
            ShortUrlInputFilter::VALID_SINCE => '',
            ShortUrlInputFilter::VALID_UNTIL => '',
            ShortUrlInputFilter::CUSTOM_SLUG => 'foobar',
            ShortUrlInputFilter::MAX_VISITS => 'invalid',
        ]];
        yield [[
            ShortUrlInputFilter::VALID_SINCE => '2017',
            ShortUrlInputFilter::MAX_VISITS => 5,
        ]];
        yield [[
            ShortUrlInputFilter::VALID_SINCE => new stdClass(),
            ShortUrlInputFilter::VALID_UNTIL => 'foo',
        ]];
        yield [[
            ShortUrlInputFilter::VALID_UNTIL => 500,
            ShortUrlInputFilter::DOMAIN => 4,
        ]];
        yield [[
            ShortUrlInputFilter::SHORT_CODE_LENGTH => 3,
        ]];
        yield [[
            ShortUrlInputFilter::CUSTOM_SLUG => '/',
        ]];
        yield [[
            ShortUrlInputFilter::CUSTOM_SLUG => '',
        ]];
        yield [[
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
        yield ['foobar', 'foobar'];
        yield ['foo bar', 'foo-bar'];
        yield ['wp-admin.php', 'wp-admin.php'];
        yield ['UPPER_lower', 'UPPER_lower'];
        yield ['more~url_special.chars', 'more~url_special.chars'];
        yield ['äéñ', 'äen'];
        yield ['구글', '구글'];
        yield ['グーグル', 'グーグル'];
        yield ['谷歌', '谷歌'];
        yield ['гугл', 'гугл'];
    }
}
