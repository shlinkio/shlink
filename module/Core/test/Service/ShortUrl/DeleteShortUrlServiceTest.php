<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Service\ShortUrl;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Exception\DeleteShortUrlException;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Options\DeleteShortUrlsOptions;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Core\Service\ShortUrl\DeleteShortUrlService;

use function Functional\map;
use function range;
use function sprintf;

class DeleteShortUrlServiceTest extends TestCase
{
    private ObjectProphecy $em;
    private string $shortCode;

    public function setUp(): void
    {
        $shortUrl = (new ShortUrl(''))->setVisits(new ArrayCollection(map(range(0, 10), function () {
            return new Visit(new ShortUrl(''), Visitor::emptyInstance());
        })));
        $this->shortCode = $shortUrl->getShortCode();

        $this->em = $this->prophesize(EntityManagerInterface::class);

        $repo = $this->prophesize(ShortUrlRepositoryInterface::class);
        $repo->findOneBy(Argument::type('array'))->willReturn($shortUrl);
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());
    }

    /** @test */
    public function deleteByShortCodeThrowsExceptionWhenThresholdIsReached(): void
    {
        $service = $this->createService();

        $this->expectException(DeleteShortUrlException::class);
        $this->expectExceptionMessage(sprintf(
            'Impossible to delete short URL with short code "%s" since it has more than "5" visits.',
            $this->shortCode
        ));

        $service->deleteByShortCode($this->shortCode);
    }

    /** @test */
    public function deleteByShortCodeDeletesUrlWhenThresholdIsReachedButExplicitlyIgnored(): void
    {
        $service = $this->createService();

        $remove = $this->em->remove(Argument::type(ShortUrl::class))->willReturn(null);
        $flush = $this->em->flush()->willReturn(null);

        $service->deleteByShortCode($this->shortCode, true);

        $remove->shouldHaveBeenCalledOnce();
        $flush->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function deleteByShortCodeDeletesUrlWhenThresholdIsReachedButCheckIsDisabled(): void
    {
        $service = $this->createService(false);

        $remove = $this->em->remove(Argument::type(ShortUrl::class))->willReturn(null);
        $flush = $this->em->flush()->willReturn(null);

        $service->deleteByShortCode($this->shortCode);

        $remove->shouldHaveBeenCalledOnce();
        $flush->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function deleteByShortCodeDeletesUrlWhenThresholdIsNotReached(): void
    {
        $service = $this->createService(true, 100);

        $remove = $this->em->remove(Argument::type(ShortUrl::class))->willReturn(null);
        $flush = $this->em->flush()->willReturn(null);

        $service->deleteByShortCode($this->shortCode);

        $remove->shouldHaveBeenCalledOnce();
        $flush->shouldHaveBeenCalledOnce();
    }

    private function createService(bool $checkVisitsThreshold = true, int $visitsThreshold = 5): DeleteShortUrlService
    {
        return new DeleteShortUrlService($this->em->reveal(), new DeleteShortUrlsOptions([
            'visitsThreshold' => $visitsThreshold,
            'checkVisitsThreshold' => $checkVisitsThreshold,
        ]));
    }
}
