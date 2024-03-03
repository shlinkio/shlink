<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl;

use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlTitleResolutionHelperInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlEdition;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\SimpleShortUrlRelationResolver;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlService;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ShortUrlServiceTest extends TestCase
{
    private ShortUrlService $service;
    private MockObject & ShortUrlResolverInterface $urlResolver;
    private MockObject & ShortUrlTitleResolutionHelperInterface $titleResolutionHelper;

    protected function setUp(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturn(null);
        $em->method('flush')->willReturn(null);

        $this->urlResolver = $this->createMock(ShortUrlResolverInterface::class);
        $this->titleResolutionHelper = $this->createMock(ShortUrlTitleResolutionHelperInterface::class);

        $this->service = new ShortUrlService(
            $em,
            $this->urlResolver,
            $this->titleResolutionHelper,
            new SimpleShortUrlRelationResolver(),
        );
    }

    #[Test, DataProvider('provideShortUrlEdits')]
    public function updateShortUrlUpdatesProvidedData(
        InvocationOrder $expectedValidateCalls,
        ShortUrlEdition $shortUrlEdit,
        ?ApiKey $apiKey,
    ): void {
        $originalLongUrl = 'https://originalLongUrl';
        $shortUrl = ShortUrl::withLongUrl($originalLongUrl);

        $this->urlResolver->expects($this->once())->method('resolveShortUrl')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain('abc123'),
            $apiKey,
        )->willReturn($shortUrl);

        $this->titleResolutionHelper->expects($expectedValidateCalls)
                                    ->method('processTitle')
                                    ->with($shortUrlEdit)
                                    ->willReturn($shortUrlEdit);

        $result = $this->service->updateShortUrl(
            ShortUrlIdentifier::fromShortCodeAndDomain('abc123'),
            $shortUrlEdit,
            $apiKey,
        );

        self::assertSame($shortUrl, $result);
        self::assertEquals($shortUrlEdit->validSince, $shortUrl->getValidSince());
        self::assertEquals($shortUrlEdit->validUntil, $shortUrl->getValidUntil());
        self::assertEquals($shortUrlEdit->maxVisits, $shortUrl->getMaxVisits());
        self::assertEquals($shortUrlEdit->longUrl ?? $originalLongUrl, $shortUrl->getLongUrl());
    }

    public static function provideShortUrlEdits(): iterable
    {
        yield 'no long URL' => [new InvokedCount(0), ShortUrlEdition::fromRawData([
            'validSince' => Chronos::parse('2017-01-01 00:00:00')->toAtomString(),
            'validUntil' => Chronos::parse('2017-01-05 00:00:00')->toAtomString(),
            'maxVisits' => 5,
        ]), null];
        yield 'long URL and API key' => [new InvokedCount(1), ShortUrlEdition::fromRawData([
            'validSince' => Chronos::parse('2017-01-01 00:00:00')->toAtomString(),
            'maxVisits' => 10,
            'longUrl' => 'https://modifiedLongUrl',
        ]), ApiKey::create()];
    }
}
