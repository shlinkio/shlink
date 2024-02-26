<?php

namespace RedirectRule\Entity;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\RedirectRule\Entity\RedirectCondition;

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
    #[TestWith(['es_ES', 'es-ES', true])] // single locale match
    #[TestWith(['en-UK', 'en-uk', true])] // different casing match
    public function matchesLanguage(?string $acceptLanguage, string $value, bool $expected): void
    {
        $request = ServerRequestFactory::fromGlobals();
        if ($acceptLanguage !== null) {
            $request = $request->withHeader('Accept-Language', $acceptLanguage);
        }

        $result = RedirectCondition::forLanguage($value)->matchesRequest($request);

        self::assertEquals($expected, $result);
    }

    #[Test, DataProvider('provideNames')]
    public function generatesExpectedName(RedirectCondition $condition, string $expectedName): void
    {
        self::assertEquals($expectedName, $condition->name);
    }

    public static function provideNames(): iterable
    {
        yield [RedirectCondition::forLanguage('es-ES'), 'language-es-ES'];
        yield [RedirectCondition::forLanguage('en_UK'), 'language-en_UK'];
        yield [RedirectCondition::forQueryParam('foo', 'bar'), 'query-foo-bar'];
        yield [RedirectCondition::forQueryParam('baz', 'foo'), 'query-baz-foo'];
    }
}
