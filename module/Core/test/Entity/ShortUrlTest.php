<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Entity;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ShortCodeCannotBeRegeneratedException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;

class ShortUrlTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideInvalidShortUrls
     */
    public function regenerateShortCodeThrowsExceptionIfStateIsInvalid(
        ShortUrl $shortUrl,
        string $expectedMessage
    ): void {
        $this->expectException(ShortCodeCannotBeRegeneratedException::class);
        $this->expectExceptionMessage($expectedMessage);

        $shortUrl->regenerateShortCode();
    }

    public function provideInvalidShortUrls(): iterable
    {
        yield 'with custom slug' => [
            new ShortUrl('', ShortUrlMeta::createFromRawData(['customSlug' => 'custom-slug'])),
            'The short code cannot be regenerated on ShortUrls where a custom slug was provided.',
        ];
        yield 'already persisted' => [
            (new ShortUrl(''))->setId('1'),
            'The short code can be regenerated only on new ShortUrls which have not been persisted yet.',
        ];
    }

    /** @test */
    public function regenerateShortCodeProperlyChangesTheValueOnValidShortUrls(): void
    {
        $shortUrl = new ShortUrl('');
        $firstShortCode = $shortUrl->getShortCode();

        $shortUrl->regenerateShortCode();
        $secondShortCode = $shortUrl->getShortCode();

        $this->assertNotEquals($firstShortCode, $secondShortCode);
    }
}
