<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\DeleteShortUrlException;
use Shlinkio\Shlink\Core\Options\DeleteShortUrlsOptions;
use Shlinkio\Shlink\Core\ShortUrl\DeleteShortUrlService;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;

use function Functional\map;
use function range;
use function sprintf;

class DeleteShortUrlServiceTest extends TestCase
{
    private MockObject & EntityManagerInterface $em;
    private MockObject & ShortUrlResolverInterface $urlResolver;
    private string $shortCode;

    protected function setUp(): void
    {
        $shortUrl = ShortUrl::createFake()->setVisits(new ArrayCollection(
            map(range(0, 10), fn () => Visit::forValidShortUrl(ShortUrl::createFake(), Visitor::emptyInstance())),
        ));
        $this->shortCode = $shortUrl->getShortCode();

        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->urlResolver = $this->createMock(ShortUrlResolverInterface::class);
        $this->urlResolver->method('resolveShortUrl')->willReturn($shortUrl);
    }

    #[Test]
    public function deleteByShortCodeThrowsExceptionWhenThresholdIsReached(): void
    {
        $service = $this->createService();

        $this->expectException(DeleteShortUrlException::class);
        $this->expectExceptionMessage(sprintf(
            'Impossible to delete short URL with short code "%s", since it has more than "5" visits.',
            $this->shortCode,
        ));

        $service->deleteByShortCode(ShortUrlIdentifier::fromShortCodeAndDomain($this->shortCode));
    }

    #[Test]
    public function deleteByShortCodeDeletesUrlWhenThresholdIsReachedButExplicitlyIgnored(): void
    {
        $service = $this->createService();

        $this->em->expects($this->once())->method('remove')->with($this->isInstanceOf(ShortUrl::class))->willReturn(
            null,
        );
        $this->em->expects($this->once())->method('flush')->with()->willReturn(null);

        $service->deleteByShortCode(ShortUrlIdentifier::fromShortCodeAndDomain($this->shortCode), true);
    }

    #[Test]
    public function deleteByShortCodeDeletesUrlWhenThresholdIsReachedButCheckIsDisabled(): void
    {
        $service = $this->createService(false);

        $this->em->expects($this->once())->method('remove')->with($this->isInstanceOf(ShortUrl::class))->willReturn(
            null,
        );
        $this->em->expects($this->once())->method('flush')->with()->willReturn(null);

        $service->deleteByShortCode(ShortUrlIdentifier::fromShortCodeAndDomain($this->shortCode));
    }

    #[Test]
    public function deleteByShortCodeDeletesUrlWhenThresholdIsNotReached(): void
    {
        $service = $this->createService(true, 100);

        $this->em->expects($this->once())->method('remove')->with($this->isInstanceOf(ShortUrl::class))->willReturn(
            null,
        );
        $this->em->expects($this->once())->method('flush')->with()->willReturn(null);

        $service->deleteByShortCode(ShortUrlIdentifier::fromShortCodeAndDomain($this->shortCode));
    }

    private function createService(bool $checkVisitsThreshold = true, int $visitsThreshold = 5): DeleteShortUrlService
    {
        return new DeleteShortUrlService($this->em, new DeleteShortUrlsOptions(
            $visitsThreshold,
            $checkVisitsThreshold,
        ), $this->urlResolver);
    }
}
