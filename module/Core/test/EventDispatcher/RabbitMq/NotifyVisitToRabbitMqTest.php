<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher\RabbitMq;

use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shlinkio\Shlink\Common\RabbitMq\RabbitMqPublishingHelperInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\EventDispatcher\Event\VisitLocated;
use Shlinkio\Shlink\Core\EventDispatcher\RabbitMq\NotifyVisitToRabbitMq;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifier;
use Shlinkio\Shlink\Core\ShortUrl\Transformer\ShortUrlDataTransformer;
use Shlinkio\Shlink\Core\Visit\Transformer\OrphanVisitDataTransformer;
use Throwable;

use function count;
use function Functional\contains;

class NotifyVisitToRabbitMqTest extends TestCase
{
    use ProphecyTrait;

    private NotifyVisitToRabbitMq $listener;
    private ObjectProphecy $helper;
    private ObjectProphecy $em;
    private ObjectProphecy $logger;

    protected function setUp(): void
    {
        $this->helper = $this->prophesize(RabbitMqPublishingHelperInterface::class);
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        $this->listener = new NotifyVisitToRabbitMq(
            $this->helper->reveal(),
            $this->em->reveal(),
            $this->logger->reveal(),
            new OrphanVisitDataTransformer(),
            new ShortUrlDataTransformer(new ShortUrlStringifier([])),
            true,
        );
    }

    /** @test */
    public function doesNothingWhenTheFeatureIsNotEnabled(): void
    {
        $listener = new NotifyVisitToRabbitMq(
            $this->helper->reveal(),
            $this->em->reveal(),
            $this->logger->reveal(),
            new OrphanVisitDataTransformer(),
            new ShortUrlDataTransformer(new ShortUrlStringifier([])),
            false,
        );

        $listener(new VisitLocated('123'));

        $this->em->find(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->logger->warning(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->logger->debug(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->helper->publishPayloadInQueue(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    /** @test */
    public function notificationsAreNotSentWhenVisitCannotBeFound(): void
    {
        $visitId = '123';
        $findVisit = $this->em->find(Visit::class, $visitId)->willReturn(null);
        $logWarning = $this->logger->warning(
            'Tried to notify RabbitMQ for visit with id "{visitId}", but it does not exist.',
            ['visitId' => $visitId],
        );

        ($this->listener)(new VisitLocated($visitId));

        $findVisit->shouldHaveBeenCalledOnce();
        $logWarning->shouldHaveBeenCalledOnce();
        $this->logger->debug(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->helper->publishPayloadInQueue(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     * @dataProvider provideVisits
     */
    public function expectedChannelsAreNotifiedBasedOnTheVisitType(Visit $visit, array $expectedChannels): void
    {
        $visitId = '123';
        $findVisit = $this->em->find(Visit::class, $visitId)->willReturn($visit);
        $argumentWithExpectedChannels = Argument::that(
            static fn (string $channel) => contains($expectedChannels, $channel),
        );

        ($this->listener)(new VisitLocated($visitId));

        $findVisit->shouldHaveBeenCalledOnce();
        $this->helper->publishPayloadInQueue(
            Argument::type('array'),
            $argumentWithExpectedChannels,
        )->shouldHaveBeenCalledTimes(count($expectedChannels));
        $this->logger->debug(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    public function provideVisits(): iterable
    {
        $visitor = Visitor::emptyInstance();

        yield 'orphan visit' => [Visit::forBasePath($visitor), ['https://shlink.io/new-orphan-visit']];
        yield 'non-orphan visit' => [
            Visit::forValidShortUrl(
                ShortUrl::fromMeta(ShortUrlMeta::fromRawData([
                    'longUrl' => 'foo',
                    'customSlug' => 'bar',
                ])),
                $visitor,
            ),
            ['https://shlink.io/new-visit', 'https://shlink.io/new-visit/bar'],
        ];
    }

    /**
     * @test
     * @dataProvider provideExceptions
     */
    public function printsDebugMessageInCaseOfError(Throwable $e): void
    {
        $visitId = '123';
        $findVisit = $this->em->find(Visit::class, $visitId)->willReturn(Visit::forBasePath(Visitor::emptyInstance()));
        $publish = $this->helper->publishPayloadInQueue(Argument::cetera())->willThrow($e);

        ($this->listener)(new VisitLocated($visitId));

        $this->logger->debug(
            'Error while trying to notify RabbitMQ with new visit. {e}',
            ['e' => $e],
        )->shouldHaveBeenCalledOnce();
        $findVisit->shouldHaveBeenCalledOnce();
        $publish->shouldHaveBeenCalledOnce();
    }

    public function provideExceptions(): iterable
    {
        yield [new RuntimeException('RuntimeException Error')];
        yield [new Exception('Exception Error')];
        yield [new DomainException('DomainException Error')];
    }
}
