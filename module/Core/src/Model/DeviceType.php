<?php

namespace Shlinkio\Shlink\Core\Model;

use foroco\BrowserDetection;

enum DeviceType: string
{
    case ANDROID = 'android';
    case IOS = 'ios';
    case DESKTOP = 'desktop';

    public static function matchFromUserAgent(string $userAgent): self|null
    {
        static $detection = null;
        if ($detection === null) {
            $detection = new BrowserDetection();
        }
        ['os_family' => $osFamily, 'os_type' => $osType, 'os_name' => $osName] = $detection->getOS($userAgent);

        return match (true) {
            // TODO To detect iPad only
            // TODO To detect iPhone only
            // TODO To detect Android tablets
            // TODO To detect Android phones
            $osType === 'desktop' => self::DESKTOP,
            $osFamily === 'android' => self::ANDROID, // Detects both android phones and android tablets
            $osName === 'iOS' => self::IOS, // Detects both iPhone and iPad
            default => null,
        };
    }
}
