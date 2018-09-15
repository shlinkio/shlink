<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Exception;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\DeleteShortUrlException;

class DeleteShortUrlExceptionTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideMessages
     */
    public function fromVisitsThresholdGeneratesMessageProperly(
        int $threshold,
        string $shortCode,
        string $expectedMessage
    ) {
        $e = DeleteShortUrlException::fromVisitsThreshold($threshold, $shortCode);
        $this->assertEquals($expectedMessage, $e->getMessage());
    }

    public function provideMessages(): array
    {
        return [
            [
                50,
                'abc123',
                'Impossible to delete short URL with short code "abc123" since it has more than "50" visits.',
            ],
            [
                33,
                'def456',
                'Impossible to delete short URL with short code "def456" since it has more than "33" visits.',
            ],
            [
                5713,
                'foobar',
                'Impossible to delete short URL with short code "foobar" since it has more than "5713" visits.',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideThresholds
     */
    public function visitsThresholdIsProperlyReturned(int $threshold)
    {
        $e = new DeleteShortUrlException($threshold);
        $this->assertEquals($threshold, $e->getVisitsThreshold());
    }

    public function provideThresholds(): array
    {
        return \array_map(function (int $number) {
            return [$number];
        }, \range(5, 50, 5));
    }
}
