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

class DeleteShortUrlServiceTest extends TestCase
{
    /**
     * @var DeleteShortUrlService
     */
    private $service;
    /**
     * @var ObjectProphecy
     */
    private $em;

    public function setUp()
    {
        $shortUrl = (new ShortUrl(''))->setShortCode('abc123')
                                      ->setVisits(new ArrayCollection(map(range(0, 10), function () {
                                          return new Visit(new ShortUrl(''), Visitor::emptyInstance());
                                      })));

        $this->em = $this->prophesize(EntityManagerInterface::class);

        $repo = $this->prophesize(ShortUrlRepositoryInterface::class);
        $repo->findOneBy(Argument::type('array'))->willReturn($shortUrl);
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());
    }

    /**
     * @test
     */
    public function deleteByShortCodeThrowsExceptionWhenThresholdIsReached()
    {
        $service = $this->createService();

        $this->expectException(DeleteShortUrlException::class);
        $this->expectExceptionMessage(
            'Impossible to delete short URL with short code "abc123" since it has more than "5" visits.'
        );

        $service->deleteByShortCode('abc123');
    }

    /**
     * @test
     */
    public function deleteByShortCodeDeletesUrlWhenThresholdIsReachedButExplicitlyIgnored()
    {
        $service = $this->createService();

        $remove = $this->em->remove(Argument::type(ShortUrl::class))->willReturn(null);
        $flush = $this->em->flush()->willReturn(null);

        $service->deleteByShortCode('abc123', true);

        $remove->shouldHaveBeenCalledTimes(1);
        $flush->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @test
     */
    public function deleteByShortCodeDeletesUrlWhenThresholdIsReachedButCheckIsDisabled()
    {
        $service = $this->createService(false);

        $remove = $this->em->remove(Argument::type(ShortUrl::class))->willReturn(null);
        $flush = $this->em->flush()->willReturn(null);

        $service->deleteByShortCode('abc123');

        $remove->shouldHaveBeenCalledTimes(1);
        $flush->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @test
     */
    public function deleteByShortCodeDeletesUrlWhenThresholdIsNotReached()
    {
        $service = $this->createService(true, 100);

        $remove = $this->em->remove(Argument::type(ShortUrl::class))->willReturn(null);
        $flush = $this->em->flush()->willReturn(null);

        $service->deleteByShortCode('abc123');

        $remove->shouldHaveBeenCalledTimes(1);
        $flush->shouldHaveBeenCalledTimes(1);
    }

    private function createService(bool $checkVisitsThreshold = true, int $visitsThreshold = 5): DeleteShortUrlService
    {
        return new DeleteShortUrlService($this->em->reveal(), new DeleteShortUrlsOptions([
            'visitsThreshold' => $visitsThreshold,
            'checkVisitsThreshold' => $checkVisitsThreshold,
        ]));
    }
}
