<?php

namespace RedirectRule;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
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
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;

use const ShlinkioTest\Shlink\ANDROID_USER_AGENT;
use const ShlinkioTest\Shlink\DESKTOP_USER_AGENT;
use const ShlinkioTest\Shlink\IOS_USER_AGENT;

class ShortUrlRedirectionResolverTest extends TestCase
{
    private ShortUrlRedirectionResolver $resolver;
    private EntityManagerInterface & MockObject $em;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->resolver = new ShortUrlRedirectionResolver($this->em);
    }

    #[Test, DataProvider('provideData')]
    public function resolveLongUrlReturnsExpectedValue(
        ServerRequestInterface $request,
        ?RedirectCondition $condition,
        string $expectedUrl,
    ): void {
        $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'https://example.com/foo/bar',
            'deviceLongUrls' => [
                DeviceType::ANDROID->value => 'https://example.com/android',
                DeviceType::IOS->value => 'https://example.com/ios',
            ],
        ]));

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects($this->once())->method('findBy')->willReturn($condition !== null ? [
            new ShortUrlRedirectRule($shortUrl, 1, 'https://example.com/from-rule', new ArrayCollection([
                $condition,
            ])),
        ] : []);
        $this->em->expects($this->once())->method('getRepository')->with(ShortUrlRedirectRule::class)->willReturn(
            $repo,
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
        yield 'android user agent' => [
            $request(ANDROID_USER_AGENT),
            RedirectCondition::forQueryParam('foo', 'bar'), // This condition won't match
            'https://example.com/android',
        ];
        yield 'ios user agent' => [$request(IOS_USER_AGENT), null, 'https://example.com/ios'];
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
    }
}
