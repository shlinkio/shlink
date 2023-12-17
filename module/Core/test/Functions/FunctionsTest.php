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

use function array_map;
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
        yield EnvVars::class => [
            EnvVars::class,
            array_map(static fn (EnvVars $envVar) => $envVar->value, EnvVars::cases()),
        ];
        yield VisitType::class => [
            VisitType::class,
            array_map(static fn (VisitType $envVar) => $envVar->value, VisitType::cases()),
        ];
        yield DeviceType::class => [
            DeviceType::class,
            array_map(static fn (DeviceType $envVar) => $envVar->value, DeviceType::cases()),
        ];
        yield OrderableField::class => [
            OrderableField::class,
            array_map(static fn (OrderableField $envVar) => $envVar->value, OrderableField::cases()),
        ];
    }
}
