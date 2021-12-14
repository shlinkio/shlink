<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Exception;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\DeleteShortUrlException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;

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
        string $expectedMessage,
    ): void {
        $e = DeleteShortUrlException::fromVisitsThreshold(
            $threshold,
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
        );

        self::assertEquals($threshold, $e->getVisitsThreshold());
        self::assertEquals($expectedMessage, $e->getMessage());
        self::assertEquals($expectedMessage, $e->getDetail());
        self::assertEquals([
            'shortCode' => $shortCode,
            'threshold' => $threshold,
        ], $e->getAdditionalData());
        self::assertEquals('Cannot delete short URL', $e->getTitle());
        self::assertEquals('INVALID_SHORT_URL_DELETION', $e->getType());
        self::assertEquals(422, $e->getStatus());
    }

    public function provideThresholds(): array
    {
        return map(range(5, 50, 5), function (int $number) {
            return [$number, $shortCode = generateRandomShortCode(6), sprintf(
                'Impossible to delete short URL with short code "%s", since it has more than "%s" visits.',
                $shortCode,
                $number,
            )];
        });
    }

    /** @test */
    public function domainIsPartOfAdditionalWhenProvidedInIdentifier(): void
    {
        $e = DeleteShortUrlException::fromVisitsThreshold(
            10,
            ShortUrlIdentifier::fromShortCodeAndDomain('abc123', 'doma.in'),
        );
        $expectedMessage = 'Impossible to delete short URL with short code "abc123" for domain "doma.in", since it '
            . 'has more than "10" visits.';

        self::assertEquals([
            'shortCode' => 'abc123',
            'domain' => 'doma.in',
            'threshold' => 10,
        ], $e->getAdditionalData());
        self::assertEquals($expectedMessage, $e->getMessage());
        self::assertEquals($expectedMessage, $e->getDetail());
    }
}
