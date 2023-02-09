<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Visit\Entity;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Visit\Entity\VisitLocation;
use Shlinkio\Shlink\IpGeolocation\Model\Location;

class VisitLocationTest extends TestCase
{
    #[Test, DataProvider('provideArgs')]
    public function isEmptyReturnsTrueWhenAllValuesAreEmpty(array $args, bool $isEmpty): void
    {
        $payload = new Location(...$args);
        $location = VisitLocation::fromGeolocation($payload);

        self::assertEquals($isEmpty, $location->isEmpty());
    }

    public static function provideArgs(): iterable
    {
        yield [['', '', '', '', 0.0, 0.0, ''], true];
        yield [['', '', '', '', 0.0, 0.0, 'dd'], false];
        yield [['', '', '', 'dd', 0.0, 0.0, ''], false];
        yield [['', '', 'dd', '', 0.0, 0.0, ''], false];
        yield [['', 'dd', '', '', 0.0, 0.0, ''], false];
        yield [['dd', '', '', '', 0.0, 0.0, ''], false];
        yield [['', '', '', '', 1.0, 0.0, ''], false];
        yield [['', '', '', '', 0.0, 1.0, ''], false];
    }
}
