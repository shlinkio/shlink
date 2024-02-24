<?php

namespace RedirectRule;

use Doctrine\ORM\EntityManagerInterface;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Model\DeviceType;
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
    public function resolveLongUrlReturnsExpectedValue(ServerRequestInterface $request, string $expectedUrl): void
    {
        $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'https://example.com/foo/bar',
            'deviceLongUrls' => [
                DeviceType::ANDROID->value => 'https://example.com/android',
                DeviceType::IOS->value => 'https://example.com/ios',
            ],
        ]));

        $result = $this->resolver->resolveLongUrl($shortUrl, $request);

        self::assertEquals($expectedUrl, $result);
    }

    public static function provideData(): iterable
    {
        $request = static fn (string $userAgent = '') => ServerRequestFactory::fromGlobals()->withHeader(
            'User-Agent',
            $userAgent,
        );

        yield 'unknown user agent' => [$request('Unknown'), 'https://example.com/foo/bar'];
        yield 'desktop user agent' => [$request(DESKTOP_USER_AGENT), 'https://example.com/foo/bar'];
        yield 'android user agent' => [$request(ANDROID_USER_AGENT), 'https://example.com/android'];
        yield 'ios user agent' => [$request(IOS_USER_AGENT), 'https://example.com/ios'];
    }
}
