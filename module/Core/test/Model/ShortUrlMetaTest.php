<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Model;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Validation\ShortUrlMetaInputFilter;

class ShortUrlMetaTest extends TestCase
{
    /**
     * @param array $data
     * @test
     * @dataProvider provideInvalidData
     */
    public function exceptionIsThrownIfProvidedDataIsInvalid(array $data)
    {
        $this->expectException(ValidationException::class);
        ShortUrlMeta::createFromRawData($data);
    }

    public function provideInvalidData(): array
    {
        return [
            [[
                ShortUrlMetaInputFilter::VALID_SINCE => '',
                ShortUrlMetaInputFilter::VALID_UNTIL => '',
                ShortUrlMetaInputFilter::CUSTOM_SLUG => 'foobar',
                ShortUrlMetaInputFilter::MAX_VISITS => 'invalid',
            ]],
            [[
                ShortUrlMetaInputFilter::VALID_SINCE => '2017',
                ShortUrlMetaInputFilter::MAX_VISITS => 5,
            ]],
        ];
    }

    /**
     * @test
     */
    public function properlyCreatedInstanceReturnsValues()
    {
        $meta = ShortUrlMeta::createFromParams((new \DateTime('2015-01-01'))->format(\DateTime::ATOM), null, 'foobar');

        $this->assertTrue($meta->hasValidSince());
        $this->assertEquals(new \DateTime('2015-01-01'), $meta->getValidSince());

        $this->assertFalse($meta->hasValidUntil());
        $this->assertNull($meta->getValidUntil());

        $this->assertTrue($meta->hasCustomSlug());
        $this->assertEquals('foobar', $meta->getCustomSlug());

        $this->assertFalse($meta->hasMaxVisits());
        $this->assertNull($meta->getMaxVisits());
    }
}
