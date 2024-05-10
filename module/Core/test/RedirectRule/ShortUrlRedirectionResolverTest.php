<?php

namespace ShlinkioTest\Shlink\Core\RedirectRule;

use Doctrine\Common\Collections\ArrayCollection;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\RedirectRule\Entity\RedirectCondition;
use Shlinkio\Shlink\Core\RedirectRule\Entity\ShortUrlRedirectRule;
use Shlinkio\Shlink\Core\RedirectRule\ShortUrlRedirectionResolver;
use Shlinkio\Shlink\Core\RedirectRule\ShortUrlRedirectRuleServiceInterface;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;

use const ShlinkioTest\Shlink\ANDROID_USER_AGENT;
use const ShlinkioTest\Shlink\DESKTOP_USER_AGENT;
use const ShlinkioTest\Shlink\IOS_USER_AGENT;

class ShortUrlRedirectionResolverTest extends TestCase
{
    private ShortUrlRedirectionResolver $resolver;
    private ShortUrlRedirectRuleServiceInterface & MockObject $ruleService;

    protected function setUp(): void
    {
        $this->ruleService = $this->createMock(ShortUrlRedirectRuleServiceInterface::class);
        $this->resolver = new ShortUrlRedirectionResolver($this->ruleService);
    }

    #[Test, DataProvider('provideData')]
    public function resolveLongUrlReturnsExpectedValue(
        ServerRequestInterface $request,
        ?RedirectCondition $condition,
        string $expectedUrl,
    ): void {
        $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'https://example.com/foo/bar',
        ]));

        // We're not in a web-browser, so we must override the $_SERVER["HTTP_X_FORWARDED_FOR"] variable
        if ($request->hasHeader('X-Forwarded-For')) {
            $_SERVER['HTTP_X_FORWARDED_FOR'] = $request->getHeader('X-Forwarded-For')[0];
        }

        $this->ruleService->expects($this->once())->method('rulesForShortUrl')->with($shortUrl)->willReturn(
            $condition !== null ? [
                new ShortUrlRedirectRule($shortUrl, 1, 'https://example.com/from-rule', new ArrayCollection([
                    $condition,
                ])),
            ] : [],
        );

        $result = $this->resolver->resolveLongUrl($shortUrl, $request);

        self::assertEquals($expectedUrl, $result);
    }

    public static function provideData(): iterable
    {
        $request = static fn (string $userAgent = '') => ServerRequestFactory::fromGlobals()->withHeader(
            'User-Agent',
            $userAgent,
        );

        yield 'unknown user agent' => [
            $request('Unknown'), // This user agent won't match any device
            RedirectCondition::forLanguage('es-ES'), // This condition won't match
            'https://example.com/foo/bar',
        ];
        yield 'desktop user agent' => [$request(DESKTOP_USER_AGENT), null, 'https://example.com/foo/bar'];
        yield 'matching android device' => [
            $request(ANDROID_USER_AGENT),
            RedirectCondition::forDevice(DeviceType::ANDROID),
            'https://example.com/from-rule',
        ];
        yield 'matching ios device' => [
            $request(IOS_USER_AGENT),
            RedirectCondition::forDevice(DeviceType::IOS),
            'https://example.com/from-rule',
        ];
        yield 'matching language' => [
            $request()->withHeader('Accept-Language', 'es-ES'),
            RedirectCondition::forLanguage('es-ES'),
            'https://example.com/from-rule',
        ];
        yield 'matching query params' => [
            $request()->withQueryParams(['foo' => 'bar']),
            RedirectCondition::forQueryParam('foo', 'bar'),
            'https://example.com/from-rule',
        ];
        yield 'matching ip' => [
            $request()->withHeader('X-Forwarded-For', '121.121.121.121'),
            RedirectCondition::forIP('121.121.121.121'),
            'https://example.com/from-rule',
        ];
        yield 'matching unspecified ip' => [
            $request()->withHeader('X-Forwarded-For', '121.121.121.121'),
            RedirectCondition::forIP('121.121.121.122'),
            'https://example.com/foo/bar',
        ];
        yield 'matching range' => [
            $request()->withHeader('X-Forwarded-For', '121.121.121.2'),
            RedirectCondition::forIP('121.121.121.0/24'),
            'https://example.com/from-rule',
        ];
    }
}
