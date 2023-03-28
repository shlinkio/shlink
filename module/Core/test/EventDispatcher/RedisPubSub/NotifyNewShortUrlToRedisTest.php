<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher\RedisPubSub;

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
use Shlinkio\Shlink\Core\EventDispatcher\RedisPubSub\NotifyNewShortUrlToRedis;
use Shlinkio\Shlink\Core\EventDispatcher\Topic;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Throwable;

class NotifyNewShortUrlToRedisTest extends TestCase
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

        $this->createListener(false)(new ShortUrlCreated('123'));
    }

    #[Test, DataProvider('provideExceptions')]
    public function printsDebugMessageInCaseOfError(Throwable $e): void
    {
        $shortUrlId = '123';
        $update = Update::forTopicAndPayload(Topic::NEW_SHORT_URL->value, []);
        $this->em->expects($this->once())->method('find')->with(ShortUrl::class, $shortUrlId)->willReturn(
            ShortUrl::withLongUrl('longUrl'),
        );
        $this->updatesGenerator->expects($this->once())->method('newShortUrlUpdate')->with(
            $this->isInstanceOf(ShortUrl::class),
        )->willReturn($update);
        $this->helper->expects($this->once())->method('publishUpdate')->with($update)->willThrowException($e);
        $this->logger->expects($this->once())->method('debug')->with(
            'Error while trying to notify {name} with new short URL. {e}',
            ['e' => $e, 'name' => 'Redis pub/sub'],
        );

        $this->createListener()(new ShortUrlCreated($shortUrlId));
    }

    public static function provideExceptions(): iterable
    {
        yield [new RuntimeException('RuntimeException Error')];
        yield [new Exception('Exception Error')];
        yield [new DomainException('DomainException Error')];
    }

    private function createListener(bool $enabled = true): NotifyNewShortUrlToRedis
    {
        return new NotifyNewShortUrlToRedis($this->helper, $this->updatesGenerator, $this->em, $this->logger, $enabled);
    }
}
