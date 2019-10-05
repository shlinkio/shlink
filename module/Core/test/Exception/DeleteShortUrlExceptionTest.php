<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Exception;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Util\StringUtilsTrait;
use Shlinkio\Shlink\Core\Exception\DeleteShortUrlException;

use function Functional\map;
use function range;
use function sprintf;

class DeleteShortUrlExceptionTest extends TestCase
{
    use StringUtilsTrait;

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
        $this->assertEquals(0, $e->getCode());
        $this->assertNull($e->getPrevious());
    }

    /**
     * @test
     * @dataProvider provideThresholds
     */
    public function visitsThresholdIsProperlyReturned(int $threshold): void
    {
        $e = new DeleteShortUrlException($threshold);

        $this->assertEquals($threshold, $e->getVisitsThreshold());
        $this->assertEquals('', $e->getMessage());
        $this->assertEquals(0, $e->getCode());
        $this->assertNull($e->getPrevious());
    }

    public function provideThresholds(): array
    {
        return map(range(5, 50, 5), function (int $number) {
            $shortCode = $this->generateRandomString(6);
            return [$number, $shortCode, sprintf(
                'Impossible to delete short URL with short code "%s" since it has more than "%s" visits.',
                $shortCode,
                $number
            )];
        });
    }
}
