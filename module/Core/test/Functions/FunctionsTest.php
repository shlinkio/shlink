<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Functions;

use BackedEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\EnvVars;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\ShortUrl\Model\OrderableField;
use Shlinkio\Shlink\Core\Visit\Model\VisitType;

use function Functional\map;
use function Shlinkio\Shlink\Core\enumValues;

class FunctionsTest extends TestCase
{
    /**
     * @param class-string<BackedEnum> $enum
     */
    #[Test, DataProvider('provideEnums')]
    public function enumValuesReturnsExpectedValueForEnum(string $enum, array $expectedValues): void
    {
        self::assertEquals($expectedValues, enumValues($enum));
    }

    public static function provideEnums(): iterable
    {
        yield EnvVars::class => [EnvVars::class, map(EnvVars::cases(), static fn (EnvVars $envVar) => $envVar->value)];
        yield VisitType::class => [
            VisitType::class,
            map(VisitType::cases(), static fn (VisitType $envVar) => $envVar->value),
        ];
        yield DeviceType::class => [
            DeviceType::class,
            map(DeviceType::cases(), static fn (DeviceType $envVar) => $envVar->value),
        ];
        yield OrderableField::class => [
            OrderableField::class,
            map(OrderableField::cases(), static fn (OrderableField $envVar) => $envVar->value),
        ];
    }
}
