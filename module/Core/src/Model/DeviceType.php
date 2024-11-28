<?php

namespace Shlinkio\Shlink\Core\Model;

use donatj\UserAgent\Platforms;
use donatj\UserAgent\UserAgentParser;

enum DeviceType: string
{
    case ANDROID = 'android';
    case IOS = 'ios';
    case DESKTOP = 'desktop';

    public static function matchFromUserAgent(string $userAgent): self|null
    {
        static $uaParser = new UserAgentParser();
        $ua = $uaParser->parse($userAgent);

        return match ($ua->platform()) {
            Platforms::IPHONE, Platforms::IPAD => self::IOS, // Detects both iPhone and iPad (except iPadOS 13+)
            Platforms::ANDROID => self::ANDROID, // Detects both android phones and android tablets
            Platforms::LINUX, Platforms::WINDOWS, Platforms::MACINTOSH, Platforms::CHROME_OS => self::DESKTOP,
            default => null,
        };
    }
}
