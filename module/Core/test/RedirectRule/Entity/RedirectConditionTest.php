<?php

namespace ShlinkioTest\Shlink\Core\RedirectRule\Entity;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\RedirectRule\Entity\RedirectCondition;

use const ShlinkioTest\Shlink\ANDROID_USER_AGENT;
use const ShlinkioTest\Shlink\DESKTOP_USER_AGENT;
use const ShlinkioTest\Shlink\IOS_USER_AGENT;

class RedirectConditionTest extends TestCase
{
    #[Test]
    #[TestWith(['nop', '', false])] // param not present
    #[TestWith(['foo', 'not-bar', false])] // param present with wrong value
    #[TestWith(['foo', 'bar', true])] // param present with correct value
    public function matchesQueryParams(string $param, string $value, bool $expectedResult): void
    {
        $request = ServerRequestFactory::fromGlobals()->withQueryParams(['foo' => 'bar']);
        $result = RedirectCondition::forQueryParam($param, $value)->matchesRequest($request);

        self::assertEquals($expectedResult, $result);
    }

    #[Test]
    #[TestWith([null, '', false])] // no accept language
    #[TestWith(['', '', false])] // empty accept language
    #[TestWith(['*', '', false])] // wildcard accept language
    #[TestWith(['en', 'en', true])] // single language match
    #[TestWith(['es, en,fr', 'en', true])] // multiple languages match
    #[TestWith(['es, en-US,fr', 'EN', true])] // multiple locales match
    #[TestWith(['es_ES', 'es-ES', true])] // single locale match
    #[TestWith(['en-UK', 'en-uk', true])] // different casing match
    #[TestWith(['en-UK', 'en', true])] // only lang
    #[TestWith(['es-AR', 'en', false])] // different only lang
    #[TestWith(['fr', 'fr-FR', false])] // less restrictive matching locale
    public function matchesLanguage(?string $acceptLanguage, string $value, bool $expected): void
    {
        $request = ServerRequestFactory::fromGlobals();
        if ($acceptLanguage !== null) {
            $request = $request->withHeader('Accept-Language', $acceptLanguage);
        }

        $result = RedirectCondition::forLanguage($value)->matchesRequest($request);

        self::assertEquals($expected, $result);
    }

    #[Test]
    #[TestWith([null, DeviceType::ANDROID, false])]
    #[TestWith(['unknown', DeviceType::ANDROID, false])]
    #[TestWith([ANDROID_USER_AGENT, DeviceType::ANDROID, true])]
    #[TestWith([DESKTOP_USER_AGENT, DeviceType::DESKTOP, true])]
    #[TestWith([IOS_USER_AGENT, DeviceType::IOS, true])]
    #[TestWith([IOS_USER_AGENT, DeviceType::ANDROID, false])]
    #[TestWith([DESKTOP_USER_AGENT, DeviceType::IOS, false])]
    public function matchesDevice(?string $userAgent, DeviceType $value, bool $expected): void
    {
        $request = ServerRequestFactory::fromGlobals();
        if ($userAgent !== null) {
            $request = $request->withHeader('User-Agent', $userAgent);
        }

        $result = RedirectCondition::forDevice($value)->matchesRequest($request);

        self::assertEquals($expected, $result);
    }
}
