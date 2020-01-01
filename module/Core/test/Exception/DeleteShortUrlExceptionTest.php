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

        $this->assertEquals($threshold, $e->getVisitsThreshold());
        $this->assertEquals($expectedMessage, $e->getMessage());
        $this->assertEquals($expectedMessage, $e->getDetail());
        $this->assertEquals([
            'shortCode' => $shortCode,
            'threshold' => $threshold,
        ], $e->getAdditionalData());
        $this->assertEquals('Cannot delete short URL', $e->getTitle());
        $this->assertEquals('INVALID_SHORTCODE_DELETION', $e->getType());
        $this->assertEquals(422, $e->getStatus());
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
