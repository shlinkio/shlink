<?php

namespace Shlinkio\Shlink\Core\Model;

use donatj\UserAgent\Platforms;
use donatj\UserAgent\UserAgentParser;

enum DeviceType: string
{
    case ANDROID = 'android';
    case IOS = 'ios';
    case MOBILE = 'mobile';
    case WINDOWS = 'windows';
    case MACOS = 'macos';
    case LINUX = 'linux';
    case CHROMEOS = 'chromeos';
    case DESKTOP = 'desktop';

    /**
     * Determines which device types provided user agent matches. It could be more than one
     * @return self[]
     */
    public static function matchFromUserAgent(string $userAgent): array
    {
        static $uaParser = new UserAgentParser();
        $ua = $uaParser->parse($userAgent);

        return match ($ua->platform()) {
            Platforms::IPHONE, Platforms::IPAD => [self::IOS, self::MOBILE], // iPhone and iPad (except iPadOS 13+)
            Platforms::ANDROID => [self::ANDROID, self::MOBILE], // android phones and android tablets
            Platforms::LINUX => [self::LINUX, self::DESKTOP],
            Platforms::WINDOWS => [self::WINDOWS, self::DESKTOP],
            Platforms::MACINTOSH => [self::MACOS, self::DESKTOP],
            Platforms::CHROME_OS => [self::CHROMEOS, self::DESKTOP],
            default => [],
        };
    }
}
