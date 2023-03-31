<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher\RabbitMq;

use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shlinkio\Shlink\Common\UpdatePublishing\PublishingHelperInterface;
use Shlinkio\Shlink\Common\UpdatePublishing\Update;
use Shlinkio\Shlink\Core\EventDispatcher\Event\ShortUrlCreated;
use Shlinkio\Shlink\Core\EventDispatcher\PublishingUpdatesGeneratorInterface;
use Shlinkio\Shlink\Core\EventDispatcher\RabbitMq\NotifyNewShortUrlToRabbitMq;
use Shlinkio\Shlink\Core\EventDispatcher\Topic;
use Shlinkio\Shlink\Core\Options\RabbitMqOptions;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Throwable;

class NotifyNewShortUrlToRabbitMqTest extends TestCase
{
    private MockObject & PublishingHelperInterface $helper;
    private MockObject & PublishingUpdatesGeneratorInterface $updatesGenerator;
    private MockObject & EntityManagerInterface $em;
    private MockObject & LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(PublishingHelperInterface::class);
        $this->updatesGenerator = $this->createMock(PublishingUpdatesGeneratorInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    #[Test]
    public function doesNothingWhenTheFeatureIsNotEnabled(): void
    {
        $this->helper->expects($this->never())->method('publishUpdate');
        $this->em->expects($this->never())->method('find');
        $this->logger->expects($this->never())->method('warning');
        $this->logger->expects($this->never())->method('debug');

        ($this->listener(false))(new ShortUrlCreated('123'));
    }

    #[Test]
    public function notificationsAreNotSentWhenShortUrlCannotBeFound(): void
    {
        $shortUrlId = '123';
        $this->em->expects($this->once())->method('find')->with(ShortUrl::class, $shortUrlId)->willReturn(null);
        $this->logger->expects($this->once())->method('warning')->with(
            'Tried to notify {name} for new short URL with id "{shortUrlId}", but it does not exist.',
            ['shortUrlId' => $shortUrlId, 'name' => 'RabbitMQ'],
        );
        $this->logger->expects($this->never())->method('debug');
        $this->helper->expects($this->never())->method('publishUpdate');

        ($this->listener())(new ShortUrlCreated($shortUrlId));
    }

    #[Test]
    public function expectedChannelIsNotified(): void
    {
        $shortUrlId = '123';
        $update = Update::forTopicAndPayload(Topic::NEW_SHORT_URL->value, []);
        $this->em->expects($this->once())->method('find')->with(ShortUrl::class, $shortUrlId)->willReturn(
            ShortUrl::withLongUrl('https://longUrl'),
        );
        $this->updatesGenerator->expects($this->once())->method('newShortUrlUpdate')->with(
            $this->isInstanceOf(ShortUrl::class),
        )->willReturn($update);
        $this->helper->expects($this->once())->method('publishUpdate')->with($update);
        $this->logger->expects($this->never())->method('debug');

        ($this->listener())(new ShortUrlCreated($shortUrlId));
    }

    #[Test, DataProvider('provideExceptions')]
    public function printsDebugMessageInCaseOfError(Throwable $e): void
    {
        $shortUrlId = '123';
        $update = Update::forTopicAndPayload(Topic::NEW_SHORT_URL->value, []);
        $this->em->expects($this->once())->method('find')->with(ShortUrl::class, $shortUrlId)->willReturn(
            ShortUrl::withLongUrl('https://longUrl'),
        );
        $this->updatesGenerator->expects($this->once())->method('newShortUrlUpdate')->with(
            $this->isInstanceOf(ShortUrl::class),
        )->willReturn($update);
        $this->helper->expects($this->once())->method('publishUpdate')->with($update)->willThrowException($e);
        $this->logger->expects($this->once())->method('debug')->with(
            'Error while trying to notify {name} with new short URL. {e}',
            ['e' => $e, 'name' => 'RabbitMQ'],
        );

        ($this->listener())(new ShortUrlCreated($shortUrlId));
    }

    public static function provideExceptions(): iterable
    {
        yield [new RuntimeException('RuntimeException Error')];
        yield [new Exception('Exception Error')];
        yield [new DomainException('DomainException Error')];
    }

    private function listener(bool $enabled = true): NotifyNewShortUrlToRabbitMq
    {
        return new NotifyNewShortUrlToRabbitMq(
            $this->helper,
            $this->updatesGenerator,
            $this->em,
            $this->logger,
            new RabbitMqOptions($enabled),
        );
    }
}
