<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\Options\DeleteShortUrlsOptions;
use Shlinkio\Shlink\Core\Exception\DeleteShortUrlException;
use Shlinkio\Shlink\Core\ShortUrl\DeleteShortUrlService;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ExpiredShortUrlsConditions;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ExpiredShortUrlsRepository;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;

use function array_map;
use function range;
use function sprintf;

class DeleteShortUrlServiceTest extends TestCase
{
    private MockObject & EntityManagerInterface $em;
    private MockObject & ShortUrlResolverInterface $urlResolver;
    private MockObject & ExpiredShortUrlsRepository $expiredShortUrlsRepository;
    private string $shortCode;

    protected function setUp(): void
    {
        $shortUrl = ShortUrl::createFake()->setVisits(new ArrayCollection(
            array_map(fn () => Visit::forValidShortUrl(ShortUrl::createFake(), Visitor::emptyInstance()), range(0, 10)),
        ));
        $this->shortCode = $shortUrl->getShortCode();

        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->urlResolver = $this->createMock(ShortUrlResolverInterface::class);
        $this->urlResolver->method('resolveShortUrl')->willReturn($shortUrl);

        $this->expiredShortUrlsRepository = $this->createMock(ExpiredShortUrlsRepository::class);
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

    #[Test]
    public function deleteExpiredShortUrlsDelegatesToRepository(): void
    {
        $conditions = new ExpiredShortUrlsConditions();
        $this->expiredShortUrlsRepository->expects($this->once())->method('delete')->with($conditions)->willReturn(5);

        $result = $this->createService()->deleteExpiredShortUrls($conditions);

        self::assertEquals(5, $result);
    }

    #[Test]
    public function countExpiredShortUrlsDelegatesToRepository(): void
    {
        $conditions = new ExpiredShortUrlsConditions();
        $this->expiredShortUrlsRepository->expects($this->once())->method('dryCount')->with($conditions)->willReturn(2);

        $result = $this->createService()->countExpiredShortUrls($conditions);

        self::assertEquals(2, $result);
    }

    private function createService(bool $checkVisitsThreshold = true, int $visitsThreshold = 5): DeleteShortUrlService
    {
        return new DeleteShortUrlService($this->em, new DeleteShortUrlsOptions(
            $visitsThreshold,
            $checkVisitsThreshold,
        ), $this->urlResolver, $this->expiredShortUrlsRepository);
    }
}
