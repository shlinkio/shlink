<?php

declare(strict_types=1);


namespace Shlinkio\Shlink\Core\Model;

use donatj\UserAgent\Browsers;

use function Shlinkio\Shlink\Core\parseUserAgent;

enum Browser: string
{
    case CHROME = 'chrome';
    case FIREFOX = 'firefox';
    case EDGE = 'edge';
    case SAFARI = 'safari';
    case OPERA = 'opera';
    case ANDROID_BROWSER = 'android_browser';

    /**
     * Determines which browser matches provided user agent.
     */
    public static function matchFromUserAgent(string $userAgent): self|null
    {
        $ua = parseUserAgent($userAgent);

        return match ($ua->browser()) {
            Browsers::CHROME => self::CHROME,
            Browsers::FIREFOX => self::FIREFOX,
            Browsers::EDGE => self::EDGE,
            Browsers::SAFARI => self::SAFARI,
            Browsers::OPERA => self::OPERA,
            Browsers::ANDROID_BROWSER => self::ANDROID_BROWSER,
            default => null,
        };
    }
}
