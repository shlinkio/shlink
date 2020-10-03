<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Exception;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\DeleteShortUrlException;

use function Functional\map;
use function range;
use function Shlinkio\Shlink\Core\generateRandomShortCode;
use function sprintf;

class DeleteShortUrlExceptionTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideThresholds
     */
    public function fromVisitsThresholdGeneratesMessageProperly(
        int $threshold,
        string $shortCode,
        string $expectedMessage
    ): void {
        $e = DeleteShortUrlException::fromVisitsThreshold($threshold, $shortCode);

        self::assertEquals($threshold, $e->getVisitsThreshold());
        self::assertEquals($expectedMessage, $e->getMessage());
        self::assertEquals($expectedMessage, $e->getDetail());
        self::assertEquals([
            'shortCode' => $shortCode,
            'threshold' => $threshold,
        ], $e->getAdditionalData());
        self::assertEquals('Cannot delete short URL', $e->getTitle());
        self::assertEquals('INVALID_SHORTCODE_DELETION', $e->getType());
        self::assertEquals(422, $e->getStatus());
    }

    public function provideThresholds(): array
    {
        return map(range(5, 50, 5), function (int $number) {
            return [$number, $shortCode = generateRandomShortCode(6), sprintf(
                'Impossible to delete short URL with short code "%s" since it has more than "%s" visits.',
                $shortCode,
                $number,
            )];
        });
    }
}
