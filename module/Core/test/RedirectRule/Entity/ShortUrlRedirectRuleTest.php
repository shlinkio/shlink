<?php

namespace ShlinkioTest\Shlink\Core\RedirectRule\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Model\AgeMatch;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\RedirectRule\Entity\RedirectCondition;
use Shlinkio\Shlink\Core\RedirectRule\Entity\ShortUrlRedirectRule;
use Shlinkio\Shlink\Core\RedirectRule\Model\RedirectConditionType;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

use function sprintf;

class ShortUrlRedirectRuleTest extends TestCase
{
    #[Test, DataProvider('provideConditions')]
    public function matchesRequestIfAllConditionsMatch(array $conditions, bool $expectedResult): void
    {
        $request = ServerRequestFactory::fromGlobals()
            ->withHeader('Accept-Language', 'en-UK')
            ->withQueryParams(['foo' => 'bar']);

        $result = $this->createRule(new ArrayCollection($conditions))->matchesRequest($request);

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

    #[Test]
    public function conditionsCanBeCleared(): void
    {
        $conditions = new ArrayCollection(
            [RedirectCondition::forLanguage('en-UK'), RedirectCondition::forQueryParam('foo', 'bar')],
        );
        $rule = $this->createRule($conditions);

        self::assertNotEmpty($conditions);
        $rule->clearConditions();
        self::assertEmpty($conditions);
    }

    #[Test, DataProvider('provideConditionMappingCallbacks')]
    public function conditionsCanBeMapped(callable $callback, array $expectedResult): void
    {
        $conditions = new ArrayCollection([
            RedirectCondition::forLanguage('en-UK'),
            RedirectCondition::forQueryParam('foo', 'bar'),
            RedirectCondition::forDevice(DeviceType::ANDROID),
            RedirectCondition::forIpAddress('1.2.3.*'),
            RedirectCondition::forAge(AgeMatch::YOUNGER, '3600'),
        ]);
        $rule = $this->createRule($conditions);

        $result = $rule->mapConditions($callback);

        self::assertEquals($expectedResult, $result);
    }

    public static function provideConditionMappingCallbacks(): iterable
    {
        yield 'json-serialized conditions' => [fn (RedirectCondition $cond) => $cond->jsonSerialize(), [
            [
                'type' => RedirectConditionType::LANGUAGE->value,
                'matchKey' => null,
                'matchValue' => 'en-UK',
            ],
            [
                'type' => RedirectConditionType::QUERY_PARAM->value,
                'matchKey' => 'foo',
                'matchValue' => 'bar',
            ],
            [
                'type' => RedirectConditionType::DEVICE->value,
                'matchKey' => null,
                'matchValue' => DeviceType::ANDROID->value,
            ],
            [
                'type' => RedirectConditionType::IP_ADDRESS->value,
                'matchKey' => null,
                'matchValue' => '1.2.3.*',
            ],
            [
                'type' => RedirectConditionType::AGE->value,
                'matchKey' => AgeMatch::YOUNGER->value,
                'matchValue' => '3600',
            ],
        ]];
        yield 'human-friendly conditions' => [fn (RedirectCondition $cond) => $cond->toHumanFriendly(), [
            'en-UK language is accepted',
            'query string contains foo=bar',
            sprintf('device is %s', DeviceType::ANDROID->value),
            'IP address matches 1.2.3.*',
            sprintf('link age %s 3600 seconds', AgeMatch::YOUNGER->value),
        ]];
    }

    /**
     * @param ArrayCollection<int, RedirectCondition> $conditions
     */
    private function createRule(ArrayCollection $conditions): ShortUrlRedirectRule
    {
        $shortUrl = ShortUrl::withLongUrl('https://s.test');
        return new ShortUrlRedirectRule($shortUrl, 1, '', $conditions);
    }
}
