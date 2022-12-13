<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl;

use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlTitleResolutionHelperInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlEdition;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\SimpleShortUrlRelationResolver;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlService;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use ShlinkioTest\Shlink\Core\Util\ApiKeyHelpersTrait;

class ShortUrlServiceTest extends TestCase
{
    use ApiKeyHelpersTrait;

    private ShortUrlService $service;
    private MockObject & EntityManagerInterface $em;
    private MockObject & ShortUrlResolverInterface $urlResolver;
    private MockObject & ShortUrlTitleResolutionHelperInterface $titleResolutionHelper;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->em->method('persist')->willReturn(null);
        $this->em->method('flush')->willReturn(null);

        $this->urlResolver = $this->createMock(ShortUrlResolverInterface::class);
        $this->titleResolutionHelper = $this->createMock(ShortUrlTitleResolutionHelperInterface::class);

        $this->service = new ShortUrlService(
            $this->em,
            $this->urlResolver,
            $this->titleResolutionHelper,
            new SimpleShortUrlRelationResolver(),
        );
    }

    /**
     * @test
     * @dataProvider provideShortUrlEdits
     */
    public function updateShortUrlUpdatesProvidedData(
        int $expectedValidateCalls,
        ShortUrlEdition $shortUrlEdit,
        ?ApiKey $apiKey,
    ): void {
        $originalLongUrl = 'originalLongUrl';
        $shortUrl = ShortUrl::withLongUrl($originalLongUrl);

        $this->urlResolver->expects($this->once())->method('resolveShortUrl')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain('abc123'),
            $apiKey,
        )->willReturn($shortUrl);

        $this->titleResolutionHelper->expects($this->exactly($expectedValidateCalls))
                                    ->method('processTitleAndValidateUrl')
                                    ->with($shortUrlEdit)
                                    ->willReturn($shortUrlEdit);

        $result = $this->service->updateShortUrl(
            ShortUrlIdentifier::fromShortCodeAndDomain('abc123'),
            $shortUrlEdit,
            $apiKey,
        );

        self::assertSame($shortUrl, $result);
        self::assertEquals($shortUrlEdit->validSince(), $shortUrl->getValidSince());
        self::assertEquals($shortUrlEdit->validUntil(), $shortUrl->getValidUntil());
        self::assertEquals($shortUrlEdit->maxVisits(), $shortUrl->getMaxVisits());
        self::assertEquals($shortUrlEdit->longUrl() ?? $originalLongUrl, $shortUrl->getLongUrl());
    }

    public function provideShortUrlEdits(): iterable
    {
        yield 'no long URL' => [0, ShortUrlEdition::fromRawData(
            [
                'validSince' => Chronos::parse('2017-01-01 00:00:00')->toAtomString(),
                'validUntil' => Chronos::parse('2017-01-05 00:00:00')->toAtomString(),
                'maxVisits' => 5,
            ],
        ), null];
        yield 'long URL' => [1, ShortUrlEdition::fromRawData(
            [
                'validSince' => Chronos::parse('2017-01-01 00:00:00')->toAtomString(),
                'maxVisits' => 10,
                'longUrl' => 'modifiedLongUrl',
            ],
        ), ApiKey::create()];
        yield 'long URL with validation' => [1, ShortUrlEdition::fromRawData(
            [
                'longUrl' => 'modifiedLongUrl',
                'validateUrl' => true,
            ],
        ), null];
    }
}
