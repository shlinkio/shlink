<?php

namespace ShlinkioTest\Shlink\Core\RedirectRule;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\RedirectRule\Entity\RedirectCondition;
use Shlinkio\Shlink\Core\RedirectRule\Entity\ShortUrlRedirectRule;
use Shlinkio\Shlink\Core\RedirectRule\Model\RedirectConditionType;
use Shlinkio\Shlink\Core\RedirectRule\Model\RedirectRulesData;
use Shlinkio\Shlink\Core\RedirectRule\Model\Validation\RedirectRulesInputFilter;
use Shlinkio\Shlink\Core\RedirectRule\ShortUrlRedirectRuleService;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

class ShortUrlRedirectRuleServiceTest extends TestCase
{
    private EntityManagerInterface & MockObject $em;
    private ShortUrlRedirectRuleService $ruleService;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->ruleService = new ShortUrlRedirectRuleService($this->em);
    }

    #[Test]
    public function rulesForShortUrlDelegatesToRepository(): void
    {
        $shortUrl = ShortUrl::withLongUrl('https://shlink.io');
        $rules = [
            new ShortUrlRedirectRule($shortUrl, 1, 'https://example.com/from-rule', new ArrayCollection([
                RedirectCondition::forLanguage('en-US'),
            ])),
            new ShortUrlRedirectRule($shortUrl, 2, 'https://example.com/from-rule-2', new ArrayCollection([
                RedirectCondition::forQueryParam('foo', 'bar'),
                RedirectCondition::forDevice(DeviceType::ANDROID),
            ])),
        ];

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects($this->once())->method('findBy')->with(
            ['shortUrl' => $shortUrl],
            ['priority' => 'ASC'],
        )->willReturn($rules);
        $this->em->expects($this->once())->method('getRepository')->with(ShortUrlRedirectRule::class)->willReturn(
            $repo,
        );

        $result = $this->ruleService->rulesForShortUrl($shortUrl);

        self::assertSame($rules, $result);
    }

    #[Test]
    public function setRulesForShortUrlParsesProvidedData(): void
    {
        $shortUrl = ShortUrl::withLongUrl('https://example.com');
        $data = RedirectRulesData::fromRawData([
            RedirectRulesInputFilter::REDIRECT_RULES => [
                [
                    RedirectRulesInputFilter::RULE_LONG_URL => 'https://example.com/first',
                    RedirectRulesInputFilter::RULE_CONDITIONS => [
                        [
                            RedirectRulesInputFilter::CONDITION_TYPE => RedirectConditionType::DEVICE->value,
                            RedirectRulesInputFilter::CONDITION_MATCH_KEY => null,
                            RedirectRulesInputFilter::CONDITION_MATCH_VALUE => DeviceType::ANDROID->value,
                        ],
                        [
                            RedirectRulesInputFilter::CONDITION_TYPE => RedirectConditionType::QUERY_PARAM->value,
                            RedirectRulesInputFilter::CONDITION_MATCH_KEY => 'foo',
                            RedirectRulesInputFilter::CONDITION_MATCH_VALUE => 'bar',
                        ],
                    ],
                ],
                [
                    RedirectRulesInputFilter::RULE_LONG_URL => 'https://example.com/second',
                    RedirectRulesInputFilter::RULE_CONDITIONS => [
                        [
                            RedirectRulesInputFilter::CONDITION_TYPE => RedirectConditionType::DEVICE->value,
                            RedirectRulesInputFilter::CONDITION_MATCH_KEY => null,
                            RedirectRulesInputFilter::CONDITION_MATCH_VALUE => DeviceType::IOS->value,
                        ],
                    ],
                ],
            ],
        ]);

        $this->em->expects($this->once())->method('wrapInTransaction')->willReturnCallback(
            fn (callable $callback) => $callback(),
        );
        $this->em->expects($this->exactly(2))->method('persist');
        $this->em->expects($this->never())->method('remove');

        $result = $this->ruleService->setRulesForShortUrl($shortUrl, $data);

        self::assertCount(2, $result);
        self::assertInstanceOf(ShortUrlRedirectRule::class, $result[0]);
        self::assertInstanceOf(ShortUrlRedirectRule::class, $result[1]);
    }

    #[Test]
    public function setRulesForShortUrlRemovesOldRules(): void
    {
        $shortUrl = ShortUrl::withLongUrl('https://example.com');
        $data = RedirectRulesData::fromRawData([
            RedirectRulesInputFilter::REDIRECT_RULES => [],
        ]);

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects($this->once())->method('findBy')->with(
            ['shortUrl' => $shortUrl],
            ['priority' => 'ASC'],
        )->willReturn([
            new ShortUrlRedirectRule($shortUrl, 1, 'https://example.com'),
            new ShortUrlRedirectRule($shortUrl, 2, 'https://example.com'),
        ]);
        $this->em->expects($this->once())->method('getRepository')->with(ShortUrlRedirectRule::class)->willReturn(
            $repo,
        );
        $this->em->expects($this->once())->method('wrapInTransaction')->willReturnCallback(
            fn (callable $callback) => $callback(),
        );
        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->exactly(2))->method('remove');

        $result = $this->ruleService->setRulesForShortUrl($shortUrl, $data);

        self::assertCount(0, $result);
    }
}
