<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Core\Action;

use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

use const ShlinkioTest\Shlink\ANDROID_USER_AGENT;
use const ShlinkioTest\Shlink\DESKTOP_USER_AGENT;
use const ShlinkioTest\Shlink\IOS_USER_AGENT;

class RedirectTest extends ApiTestCase
{
    /**
     * @test
     * @dataProvider provideUserAgents
     */
    public function properRedirectHappensBasedOnUserAgent(?string $userAgent, string $expectedRedirect): void
    {
        $response = $this->callShortUrl('def456', $userAgent);
        self::assertEquals($expectedRedirect, $response->getHeaderLine('Location'));
    }

    public static function provideUserAgents(): iterable
    {
        yield 'android' => [ANDROID_USER_AGENT, 'https://blog.alejandrocelaya.com/android'];
        yield 'ios' => [IOS_USER_AGENT, 'https://blog.alejandrocelaya.com/ios'];
        yield 'desktop' => [
            DESKTOP_USER_AGENT,
            'https://blog.alejandrocelaya.com/2017/12/09/acmailer-7-0-the-most-important-release-in-a-long-time/',
        ];
        yield 'unknown' => [
            null,
            'https://blog.alejandrocelaya.com/2017/12/09/acmailer-7-0-the-most-important-release-in-a-long-time/',
        ];
    }
}
