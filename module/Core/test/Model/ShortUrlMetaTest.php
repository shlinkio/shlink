<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Model;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Validation\ShortUrlMetaInputFilter;
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
            ShortUrlMetaInputFilter::VALID_SINCE => '',
            ShortUrlMetaInputFilter::VALID_UNTIL => '',
            ShortUrlMetaInputFilter::CUSTOM_SLUG => 'foobar',
            ShortUrlMetaInputFilter::MAX_VISITS => 'invalid',
        ]];
        yield [[
            ShortUrlMetaInputFilter::VALID_SINCE => '2017',
            ShortUrlMetaInputFilter::MAX_VISITS => 5,
        ]];
        yield [[
            ShortUrlMetaInputFilter::VALID_SINCE => new stdClass(),
            ShortUrlMetaInputFilter::VALID_UNTIL => 'foo',
        ]];
        yield [[
            ShortUrlMetaInputFilter::VALID_UNTIL => 500,
            ShortUrlMetaInputFilter::DOMAIN => 4,
        ]];
        yield [[
            ShortUrlMetaInputFilter::SHORT_CODE_LENGTH => 3,
        ]];
        yield [[
            ShortUrlMetaInputFilter::CUSTOM_SLUG => '/',
        ]];
        yield [[
            ShortUrlMetaInputFilter::CUSTOM_SLUG => '',
        ]];
        yield [[
            ShortUrlMetaInputFilter::CUSTOM_SLUG => '   ',
        ]];
    }

    /** @test */
    public function properlyCreatedInstanceReturnsValues(): void
    {
        $meta = ShortUrlMeta::fromRawData(
            ['validSince' => Chronos::parse('2015-01-01')->toAtomString(), 'customSlug' => 'foobar'],
        );

        $this->assertTrue($meta->hasValidSince());
        $this->assertEquals(Chronos::parse('2015-01-01'), $meta->getValidSince());

        $this->assertFalse($meta->hasValidUntil());
        $this->assertNull($meta->getValidUntil());

        $this->assertTrue($meta->hasCustomSlug());
        $this->assertEquals('foobar', $meta->getCustomSlug());

        $this->assertFalse($meta->hasMaxVisits());
        $this->assertNull($meta->getMaxVisits());
    }
}
