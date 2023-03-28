<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Model;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\ShortUrl\Model\DeviceLongUrlPair;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlEdition;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;

class ShortUrlEditionTest extends TestCase
{
    #[Test, DataProvider('provideDeviceLongUrls')]
    public function expectedDeviceLongUrlsAreResolved(
        ?array $deviceLongUrls,
        array $expectedDeviceLongUrls,
        array $expectedDevicesToRemove,
    ): void {
        $edition = ShortUrlEdition::fromRawData([ShortUrlInputFilter::DEVICE_LONG_URLS => $deviceLongUrls]);

        self::assertEquals($expectedDeviceLongUrls, $edition->deviceLongUrls);
        self::assertEquals($expectedDevicesToRemove, $edition->devicesToRemove);
    }

    public static function provideDeviceLongUrls(): iterable
    {
        yield 'null' => [null, [], []];
        yield 'empty' => [[], [], []];
        yield 'only new urls' => [[
            DeviceType::DESKTOP->value => 'foo',
            DeviceType::IOS->value => 'bar',
        ], [
            DeviceType::DESKTOP->value => DeviceLongUrlPair::fromRawTypeAndLongUrl(DeviceType::DESKTOP->value, 'foo'),
            DeviceType::IOS->value => DeviceLongUrlPair::fromRawTypeAndLongUrl(DeviceType::IOS->value, 'bar'),
        ], []];
        yield 'only urls to remove' => [[
            DeviceType::ANDROID->value => null,
            DeviceType::IOS->value => null,
        ], [], [DeviceType::ANDROID, DeviceType::IOS]];
        yield 'both' => [[
            DeviceType::DESKTOP->value => 'bar',
            DeviceType::IOS->value => 'foo',
            DeviceType::ANDROID->value => null,
        ], [
            DeviceType::DESKTOP->value => DeviceLongUrlPair::fromRawTypeAndLongUrl(DeviceType::DESKTOP->value, 'bar'),
            DeviceType::IOS->value => DeviceLongUrlPair::fromRawTypeAndLongUrl(DeviceType::IOS->value, 'foo'),
        ], [DeviceType::ANDROID]];
    }
}
