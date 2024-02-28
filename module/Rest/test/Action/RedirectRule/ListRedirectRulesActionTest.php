<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\RedirectRule;

use Doctrine\Common\Collections\ArrayCollection;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\RedirectRule\Entity\RedirectCondition;
use Shlinkio\Shlink\Core\RedirectRule\Entity\ShortUrlRedirectRule;
use Shlinkio\Shlink\Core\RedirectRule\ShortUrlRedirectRuleServiceInterface;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Rest\Action\RedirectRule\ListRedirectRulesAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ListRedirectRulesActionTest extends TestCase
{
    private ShortUrlResolverInterface & MockObject $urlResolver;
    private ShortUrlRedirectRuleServiceInterface & MockObject $ruleService;
    private ListRedirectRulesAction $action;

    protected function setUp(): void
    {
        $this->urlResolver = $this->createMock(ShortUrlResolverInterface::class);
        $this->ruleService = $this->createMock(ShortUrlRedirectRuleServiceInterface::class);

        $this->action = new ListRedirectRulesAction($this->urlResolver, $this->ruleService);
    }

    #[Test]
    public function requestIsHandledAndRulesAreReturned(): void
    {
        $shortUrl = ShortUrl::withLongUrl('https://example.com');
        $request = ServerRequestFactory::fromGlobals()->withAttribute(ApiKey::class, ApiKey::create());
        $conditions = [RedirectCondition::forDevice(DeviceType::ANDROID), RedirectCondition::forLanguage('en-US')];
        $redirectRules = [
            new ShortUrlRedirectRule($shortUrl, 1, 'https://example.com/rule', new ArrayCollection($conditions)),
        ];

        $this->urlResolver->expects($this->once())->method('resolveShortUrl')->willReturn($shortUrl);
        $this->ruleService->expects($this->once())->method('rulesForShortUrl')->willReturn($redirectRules);

        /** @var JsonResponse $response */
        $response = $this->action->handle($request);
        $payload = $response->getPayload();

        self::assertEquals([
            'defaultLongUrl' => $shortUrl->getLongUrl(),
            'redirectRules' => $redirectRules,
        ], $payload);
    }
}
