<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\RedirectRule;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\RedirectRule\RedirectRuleHandler;
use Shlinkio\Shlink\CLI\RedirectRule\RedirectRuleHandlerAction;
use Shlinkio\Shlink\Core\Model\AgeMatch;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\RedirectRule\Entity\RedirectCondition;
use Shlinkio\Shlink\Core\RedirectRule\Entity\ShortUrlRedirectRule;
use Shlinkio\Shlink\Core\RedirectRule\Model\RedirectConditionType;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Symfony\Component\Console\Style\StyleInterface;

use function sprintf;

class RedirectRuleHandlerTest extends TestCase
{
    private RedirectRuleHandler $handler;
    private StyleInterface & MockObject $io;
    private ShortUrl $shortUrl;
    private RedirectCondition $cond1;
    private RedirectCondition $cond2;
    private RedirectCondition $cond3;
    /** @var ShortUrlRedirectRule[] */
    private array $rules;

    protected function setUp(): void
    {
        $this->io = $this->createMock(StyleInterface::class);
        $this->shortUrl = ShortUrl::withLongUrl('https://example.com');
        $this->cond1 = RedirectCondition::forLanguage('es-AR');
        $this->cond2 = RedirectCondition::forQueryParam('foo', 'bar');
        $this->cond3 = RedirectCondition::forDevice(DeviceType::ANDROID);
        $this->rules = [
            new ShortUrlRedirectRule($this->shortUrl, 3, 'https://example.com/one', new ArrayCollection(
                [$this->cond1],
            )),
            new ShortUrlRedirectRule($this->shortUrl, 8, 'https://example.com/two', new ArrayCollection(
                [$this->cond2, $this->cond3],
            )),
            new ShortUrlRedirectRule($this->shortUrl, 5, 'https://example.com/three', new ArrayCollection(
                [$this->cond1, $this->cond3],
            )),
        ];

        $this->handler = new RedirectRuleHandler();
    }

    #[Test,  DataProvider('provideExitActions')]
    public function commentIsDisplayedWhenRulesListIsEmpty(
        RedirectRuleHandlerAction $action,
        array|null $expectedResult,
    ): void {
        $this->io->expects($this->once())->method('choice')->willReturn($action->value);
        $this->io->expects($this->once())->method('newLine');
        $this->io->expects($this->once())->method('text')->with('<comment>// No rules found.</comment>');
        $this->io->expects($this->never())->method('table');

        $result = $this->handler->manageRules($this->io, $this->shortUrl, []);

        self::assertEquals($expectedResult, $result);
    }

    #[Test, DataProvider('provideExitActions')]
    public function rulesAreDisplayedWhenRulesListIsEmpty(
        RedirectRuleHandlerAction $action,
    ): void {
        $comment = fn (string $value) => sprintf('<comment>%s</comment>', $value);

        $this->io->expects($this->once())->method('choice')->willReturn($action->value);
        $this->io->expects($this->never())->method('newLine');
        $this->io->expects($this->never())->method('text');
        $this->io->expects($this->once())->method('table')->with($this->isArray(), [
            ['1', $comment($this->cond1->toHumanFriendly()), 'https://example.com/one'],
            [
                '2',
                $comment($this->cond2->toHumanFriendly()) . ' AND ' . $comment($this->cond3->toHumanFriendly()),
                'https://example.com/two',
            ],
            [
                '3',
                $comment($this->cond1->toHumanFriendly()) . ' AND ' . $comment($this->cond3->toHumanFriendly()),
                'https://example.com/three',
            ],
        ]);

        $this->handler->manageRules($this->io, $this->shortUrl, $this->rules);
    }

    public static function provideExitActions(): iterable
    {
        yield 'discard' => [RedirectRuleHandlerAction::DISCARD, null];
        yield 'save' => [RedirectRuleHandlerAction::SAVE, []];
    }

    #[Test, DataProvider('provideDeviceConditions')]
    /**
     * @param RedirectCondition[] $expectedConditions
     */
    public function newRulesCanBeAdded(
        RedirectConditionType $type,
        array $expectedConditions,
        bool $continue = false,
    ): void {
        $this->io->expects($this->any())->method('ask')->willReturnCallback(
            fn (string $message): string|int => match ($message) {
                'Rule priority (the lower the value, the higher the priority)' => 2, // Add in between existing rules
                'Long URL to redirect when the rule matches' => 'https://example.com/new-two',
                'Language to match?' => 'en-US',
                'Query param name?' => 'foo',
                'Query param value?' => 'bar',
                'IP address, CIDR block or wildcard-pattern (1.2.*.*)' => '1.2.3.4',
                'Country code to match?' => 'FR',
                'City name to match?' => 'Los angeles',
                'Age threshold in seconds?' => '86400',
                default => '',
            },
        );
        $this->io->expects($this->any())->method('choice')->willReturnCallback(
            function (string $message) use (&$callIndex, $type): string {
                $callIndex++;

                if ($message === 'Type of the condition?') {
                    return $type->value;
                } elseif ($message === 'Device to match?') {
                    return DeviceType::ANDROID->value;
                } elseif ($message === 'Age direction?') {
                    return AgeMatch::OLDER->value;
                }

                // First we select remove action to trigger code branch, then save to finish execution
                $action = $callIndex === 1 ? RedirectRuleHandlerAction::ADD : RedirectRuleHandlerAction::SAVE;
                return $action->value;
            },
        );

        $continueCallCount = 0;
        $this->io->method('confirm')->willReturnCallback(function () use (&$continueCallCount, $continue) {
            $continueCallCount++;
            return $continueCallCount < 2 && $continue;
        });

        $result = $this->handler->manageRules($this->io, $this->shortUrl, $this->rules);

        self::assertEquals([
            $this->rules[0],
            new ShortUrlRedirectRule($this->shortUrl, 2, 'https://example.com/new-two', new ArrayCollection(
                $expectedConditions,
            )),
            $this->rules[1],
            $this->rules[2],
        ], $result);
    }

    public static function provideDeviceConditions(): iterable
    {
        yield 'device' => [RedirectConditionType::DEVICE, [RedirectCondition::forDevice(DeviceType::ANDROID)]];
        yield 'language' => [RedirectConditionType::LANGUAGE, [RedirectCondition::forLanguage('en-US')]];
        yield 'query param' => [RedirectConditionType::QUERY_PARAM, [RedirectCondition::forQueryParam('foo', 'bar')]];
        yield 'multiple query params' => [
            RedirectConditionType::QUERY_PARAM,
            [RedirectCondition::forQueryParam('foo', 'bar'), RedirectCondition::forQueryParam('foo', 'bar')],
            true,
        ];
        yield 'IP address' => [RedirectConditionType::IP_ADDRESS, [RedirectCondition::forIpAddress('1.2.3.4')]];
        yield 'Geolocation country code' => [
            RedirectConditionType::GEOLOCATION_COUNTRY_CODE,
            [RedirectCondition::forGeolocationCountryCode('FR')],
        ];
        yield 'Geolocation city name' => [
            RedirectConditionType::GEOLOCATION_CITY_NAME,
            [RedirectCondition::forGeolocationCityName('Los angeles')],
        ];
        yield 'link age older' => [RedirectConditionType::AGE, [RedirectCondition::forAge(AgeMatch::OLDER, '86400')]];
    }

    #[Test]
    public function existingRulesCanBeRemoved(): void
    {
        $callIndex = 0;
        $this->io->expects($this->exactly(3))->method('choice')->willReturnCallback(
            function (string $message) use (&$callIndex): string {
                $callIndex++;

                if ($message === 'What rule do you want to delete?') {
                    return '2 - https://example.com/two'; // Second rule to be removed
                }

                // First we select remove action to trigger code branch, then save to finish execution
                $action = $callIndex === 1 ? RedirectRuleHandlerAction::REMOVE : RedirectRuleHandlerAction::SAVE;
                return $action->value;
            },
        );
        $this->io->expects($this->never())->method('warning');

        $result = $this->handler->manageRules($this->io, $this->shortUrl, $this->rules);

        self::assertEquals([$this->rules[0], $this->rules[2]], $result);
    }

    #[Test]
    public function warningIsPrintedWhenTryingToRemoveRuleFromEmptyList(): void
    {
        $callIndex = 0;
        $this->io->expects($this->exactly(2))->method('choice')->willReturnCallback(
            function () use (&$callIndex): string {
                $callIndex++;
                $action = $callIndex === 1 ? RedirectRuleHandlerAction::REMOVE : RedirectRuleHandlerAction::DISCARD;
                return $action->value;
            },
        );
        $this->io->expects($this->once())->method('warning')->with('There are no rules to remove');

        $this->handler->manageRules($this->io, $this->shortUrl, []);
    }

    #[Test]
    public function existingRulesCanBeReArranged(): void
    {
        $this->io->expects($this->any())->method('ask')->willReturnCallback(
            fn (string $message): string|int => match ($message) {
                'Rule priority (the lower the value, the higher the priority)' => 1,
                default => '',
            },
        );
        $this->io->expects($this->exactly(3))->method('choice')->willReturnCallback(
            function (string $message) use (&$callIndex): string {
                $callIndex++;

                if ($message === 'What rule do you want to re-arrange?') {
                    return '2 - https://example.com/two'; // Second rule to be re-arrange
                }

                // First we select remove action to trigger code branch, then save to finish execution
                $action = $callIndex === 1 ? RedirectRuleHandlerAction::RE_ARRANGE : RedirectRuleHandlerAction::SAVE;
                return $action->value;
            },
        );
        $this->io->expects($this->never())->method('warning');

        $result = $this->handler->manageRules($this->io, $this->shortUrl, $this->rules);

        self::assertEquals([$this->rules[1], $this->rules[0], $this->rules[2]], $result);
    }

    #[Test]
    public function warningIsPrintedWhenTryingToReArrangeRuleFromEmptyList(): void
    {
        $callIndex = 0;
        $this->io->expects($this->exactly(2))->method('choice')->willReturnCallback(
            function () use (&$callIndex): string {
                $callIndex++;
                $action = $callIndex === 1 ? RedirectRuleHandlerAction::RE_ARRANGE : RedirectRuleHandlerAction::DISCARD;
                return $action->value;
            },
        );
        $this->io->expects($this->once())->method('warning')->with('There are no rules to re-arrange');

        $this->handler->manageRules($this->io, $this->shortUrl, []);
    }
}
