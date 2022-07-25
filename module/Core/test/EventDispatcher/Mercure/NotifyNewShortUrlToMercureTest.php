<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher\Mercure;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\EventDispatcher\Event\ShortUrlCreated;
use Shlinkio\Shlink\Core\EventDispatcher\Mercure\NotifyNewShortUrlToMercure;
use Shlinkio\Shlink\Core\Mercure\MercureUpdatesGeneratorInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class NotifyNewShortUrlToMercureTest extends TestCase
{
    use ProphecyTrait;

    private NotifyNewShortUrlToMercure $listener;
    private ObjectProphecy $hub;
    private ObjectProphecy $updatesGenerator;
    private ObjectProphecy $em;
    private ObjectProphecy $logger;

    protected function setUp(): void
    {
        $this->hub = $this->prophesize(HubInterface::class);
        $this->updatesGenerator = $this->prophesize(MercureUpdatesGeneratorInterface::class);
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        $this->listener = new NotifyNewShortUrlToMercure(
            $this->hub->reveal(),
            $this->updatesGenerator->reveal(),
            $this->em->reveal(),
            $this->logger->reveal(),
        );
    }

    /** @test */
    public function messageIsLoggedWhenShortUrlIsNotFound(): void
    {
        $find = $this->em->find(ShortUrl::class, '123')->willReturn(null);

        ($this->listener)(new ShortUrlCreated('123'));

        $find->shouldHaveBeenCalledOnce();
        $this->logger->warning(
            'Tried to notify Mercure for new short URL with id "{shortUrlId}", but it does not exist.',
            ['shortUrlId' => '123'],
        )->shouldHaveBeenCalledOnce();
        $this->hub->publish(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->updatesGenerator->newShortUrlUpdate(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->logger->debug(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    /** @test */
    public function expectedNotificationIsPublished(): void
    {
        $shortUrl = ShortUrl::withLongUrl('');
        $update = new Update([]);

        $find = $this->em->find(ShortUrl::class, '123')->willReturn($shortUrl);
        $newUpdate = $this->updatesGenerator->newShortUrlUpdate($shortUrl)->willReturn($update);
        $publish = $this->hub->publish($update)->willReturn('');

        ($this->listener)(new ShortUrlCreated('123'));

        $find->shouldHaveBeenCalledOnce();
        $newUpdate->shouldHaveBeenCalledOnce();
        $publish->shouldHaveBeenCalledOnce();
        $this->logger->warning(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->logger->debug(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    /** @test */
    public function messageIsPrintedIfPublishingFails(): void
    {
        $shortUrl = ShortUrl::withLongUrl('');
        $update = new Update([]);
        $e = new Exception('Error');

        $find = $this->em->find(ShortUrl::class, '123')->willReturn($shortUrl);
        $newUpdate = $this->updatesGenerator->newShortUrlUpdate($shortUrl)->willReturn($update);
        $publish = $this->hub->publish($update)->willThrow($e);

        ($this->listener)(new ShortUrlCreated('123'));

        $find->shouldHaveBeenCalledOnce();
        $newUpdate->shouldHaveBeenCalledOnce();
        $publish->shouldHaveBeenCalledOnce();
        $this->logger->warning(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->logger->debug(
            'Error while trying to notify mercure hub with new short URL. {e}',
            ['e' => $e],
        )->shouldHaveBeenCalledOnce();
    }
}
