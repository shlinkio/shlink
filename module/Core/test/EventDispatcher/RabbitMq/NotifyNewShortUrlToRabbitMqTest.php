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
        ($this->listener(false))(new ShortUrlCreated('123'));

        $this->em->find(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->logger->warning(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->logger->debug(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->helper->publishUpdate(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    /** @test */
    public function notificationsAreNotSentWhenShortUrlCannotBeFound(): void
    {
        $shortUrlId = '123';
        $find = $this->em->find(ShortUrl::class, $shortUrlId)->willReturn(null);
        $logWarning = $this->logger->warning(
            'Tried to notify {name} for new short URL with id "{shortUrlId}", but it does not exist.',
            ['shortUrlId' => $shortUrlId, 'name' => 'RabbitMQ'],
        );

        ($this->listener())(new ShortUrlCreated($shortUrlId));

        $find->shouldHaveBeenCalledOnce();
        $logWarning->shouldHaveBeenCalledOnce();
        $this->logger->debug(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->helper->publishUpdate(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    /** @test */
    public function expectedChannelIsNotified(): void
    {
        $shortUrlId = '123';
        $update = Update::forTopicAndPayload(Topic::NEW_SHORT_URL->value, []);
        $find = $this->em->find(ShortUrl::class, $shortUrlId)->willReturn(ShortUrl::withLongUrl(''));
        $generateUpdate = $this->updatesGenerator->newShortUrlUpdate(Argument::type(ShortUrl::class))->willReturn(
            $update,
        );

        ($this->listener())(new ShortUrlCreated($shortUrlId));

        $find->shouldHaveBeenCalledOnce();
        $generateUpdate->shouldHaveBeenCalledOnce();
        $this->helper->publishUpdate($update)->shouldHaveBeenCalledOnce();
        $this->logger->debug(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     * @dataProvider provideExceptions
     */
    public function printsDebugMessageInCaseOfError(Throwable $e): void
    {
        $shortUrlId = '123';
        $update = Update::forTopicAndPayload(Topic::NEW_SHORT_URL->value, []);
        $find = $this->em->find(ShortUrl::class, $shortUrlId)->willReturn(ShortUrl::withLongUrl(''));
        $generateUpdate = $this->updatesGenerator->newShortUrlUpdate(Argument::type(ShortUrl::class))->willReturn(
            $update,
        );
        $publish = $this->helper->publishUpdate($update)->willThrow($e);

        ($this->listener())(new ShortUrlCreated($shortUrlId));

        $this->logger->debug(
            'Error while trying to notify {name} with new short URL. {e}',
            ['e' => $e, 'name' => 'RabbitMQ'],
        )->shouldHaveBeenCalledOnce();
        $find->shouldHaveBeenCalledOnce();
        $generateUpdate->shouldHaveBeenCalledOnce();
        $publish->shouldHaveBeenCalledOnce();
    }

    public function provideExceptions(): iterable
    {
        yield [new RuntimeException('RuntimeException Error')];
        yield [new Exception('Exception Error')];
        yield [new DomainException('DomainException Error')];
    }

    private function listener(bool $enabled = true): NotifyNewShortUrlToRabbitMq
    {
        return new NotifyNewShortUrlToRabbitMq(
            $this->helper->reveal(),
            $this->updatesGenerator->reveal(),
            $this->em->reveal(),
            $this->logger->reveal(),
            new RabbitMqOptions($enabled),
        );
    }
}
