<?php

namespace Shlinkio\Shlink\Core\Model;

use Detection\MobileDetect;

enum DeviceType: string
{
    case ANDROID = 'android';
    case IOS = 'ios';
    case DESKTOP = 'desktop';

    public static function matchFromUserAgent(string $userAgent): ?self
    {
        $detect = new MobileDetect(null, $userAgent); // @phpstan-ignore-line

        return match (true) {
//            $detect->is('iOS') && $detect->isTablet() => self::IOS, // TODO To detect iPad only
//            $detect->is('iOS') && ! $detect->isTablet() => self::IOS, // TODO To detect iPhone only
//            $detect->is('androidOS') && $detect->isTablet() => self::ANDROID, // TODO To detect Android tablets
//            $detect->is('androidOS') && ! $detect->isTablet() => self::ANDROID, // TODO To detect Android phones
            $detect->is('iOS') => self::IOS, // Detects both iPhone and iPad
            $detect->is('androidOS') => self::ANDROID, // Detects both android phones and android tablets
            ! $detect->isMobile() && ! $detect->isTablet() => self::DESKTOP,
            default => null,
        };
    }
}
