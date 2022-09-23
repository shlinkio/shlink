<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher\RedisPubSub;

use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
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
    use ProphecyTrait;

    private ObjectProphecy $helper;
    private ObjectProphecy $updatesGenerator;
    private ObjectProphecy $em;
    private ObjectProphecy $logger;

    protected function setUp(): void
    {
        $this->helper = $this->prophesize(PublishingHelperInterface::class);
        $this->updatesGenerator = $this->prophesize(PublishingUpdatesGeneratorInterface::class);
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
    }

    /** @test */
    public function doesNothingWhenTheFeatureIsNotEnabled(): void
    {
        $this->createListener(false)(new VisitLocated('123'));

        $this->em->find(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->logger->warning(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->logger->debug(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->helper->publishUpdate(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     * @dataProvider provideExceptions
     */
    public function printsDebugMessageInCaseOfError(Throwable $e): void
    {
        $visitId = '123';
        $findVisit = $this->em->find(Visit::class, $visitId)->willReturn(Visit::forBasePath(Visitor::emptyInstance()));
        $generateUpdate = $this->updatesGenerator->newOrphanVisitUpdate(Argument::type(Visit::class))->willReturn(
            Update::forTopicAndPayload('', []),
        );
        $publish = $this->helper->publishUpdate(Argument::cetera())->willThrow($e);

        $this->createListener()(new VisitLocated($visitId));

        $this->logger->debug(
            'Error while trying to notify {name} with new visit. {e}',
            ['e' => $e, 'name' => 'Redis pub/sub'],
        )->shouldHaveBeenCalledOnce();
        $findVisit->shouldHaveBeenCalledOnce();
        $generateUpdate->shouldHaveBeenCalledOnce();
        $publish->shouldHaveBeenCalledOnce();
    }

    public function provideExceptions(): iterable
    {
        yield [new RuntimeException('RuntimeException Error')];
        yield [new Exception('Exception Error')];
        yield [new DomainException('DomainException Error')];
    }

    private function createListener(bool $enabled = true): NotifyVisitToRedis
    {
        return new NotifyVisitToRedis(
            $this->helper->reveal(),
            $this->updatesGenerator->reveal(),
            $this->em->reveal(),
            $this->logger->reveal(),
            $enabled,
        );
    }
}
