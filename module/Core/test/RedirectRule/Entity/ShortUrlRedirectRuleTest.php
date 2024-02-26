<?php

namespace RedirectRule\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\RedirectRule\Entity\RedirectCondition;
use Shlinkio\Shlink\Core\RedirectRule\Entity\ShortUrlRedirectRule;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

class ShortUrlRedirectRuleTest extends TestCase
{
    #[Test, DataProvider('provideConditions')]
    public function matchesRequestIfAllConditionsMatch(array $conditions, bool $expectedResult): void
    {
        $request = ServerRequestFactory::fromGlobals()
            ->withHeader('Accept-Language', 'en-UK')
            ->withQueryParams(['foo' => 'bar']);

        $result = $this->createRule($conditions)->matchesRequest($request);

        self::assertEquals($expectedResult, $result);
    }

    public static function provideConditions(): iterable
    {
        yield 'no conditions' => [[], false];
        yield 'not all conditions match' => [
            [RedirectCondition::forLanguage('en-UK'), RedirectCondition::forQueryParam('foo', 'foo')],
            false,
        ];
        yield 'all conditions match' => [
            [RedirectCondition::forLanguage('en-UK'), RedirectCondition::forQueryParam('foo', 'bar')],
            true,
        ];
    }

    /**
     * @param RedirectCondition[] $conditions
     */
    private function createRule(array $conditions): ShortUrlRedirectRule
    {
        $shortUrl = ShortUrl::withLongUrl('https://s.test');
        return new ShortUrlRedirectRule($shortUrl, 1, '', new ArrayCollection($conditions));
    }
}
