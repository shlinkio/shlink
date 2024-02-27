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
    public function delegatesToRepository(): void
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
}
