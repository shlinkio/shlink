<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher\RedisPubSub;

use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shlinkio\Shlink\Common\UpdatePublishing\PublishingHelperInterface;
use Shlinkio\Shlink\Common\UpdatePublishing\Update;
use Shlinkio\Shlink\Core\EventDispatcher\Event\VisitLocated;
use Shlinkio\Shlink\Core\EventDispatcher\PublishingUpdatesGeneratorInterface;
use Shlinkio\Shlink\Core\EventDispatcher\RedisPubSub\NotifyVisitToRedis;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Throwable;

class NotifyVisitToRedisTest extends TestCase
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

    /** @test */
    public function doesNothingWhenTheFeatureIsNotEnabled(): void
    {
        $this->helper->expects($this->never())->method('publishUpdate');
        $this->em->expects($this->never())->method('find');
        $this->logger->expects($this->never())->method('warning');
        $this->logger->expects($this->never())->method('debug');

        $this->createListener(false)(new VisitLocated('123'));
    }

    /**
     * @test
     * @dataProvider provideExceptions
     */
    public function printsDebugMessageInCaseOfError(Throwable $e): void
    {
        $visitId = '123';
        $this->em->expects($this->once())->method('find')->with(Visit::class, $visitId)->willReturn(
            Visit::forBasePath(Visitor::emptyInstance()),
        );
        $this->updatesGenerator->expects($this->once())->method('newOrphanVisitUpdate')->with(
            $this->isInstanceOf(Visit::class),
        )->willReturn(Update::forTopicAndPayload('', []));
        $this->helper->expects($this->once())->method('publishUpdate')->withAnyParameters()->willThrowException($e);
        $this->logger->expects($this->once())->method('debug')->with(
            'Error while trying to notify {name} with new visit. {e}',
            ['e' => $e, 'name' => 'Redis pub/sub'],
        );

        $this->createListener()(new VisitLocated($visitId));
    }

    public static function provideExceptions(): iterable
    {
        yield [new RuntimeException('RuntimeException Error')];
        yield [new Exception('Exception Error')];
        yield [new DomainException('DomainException Error')];
    }

    private function createListener(bool $enabled = true): NotifyVisitToRedis
    {
        return new NotifyVisitToRedis($this->helper, $this->updatesGenerator, $this->em, $this->logger, $enabled);
    }
}
