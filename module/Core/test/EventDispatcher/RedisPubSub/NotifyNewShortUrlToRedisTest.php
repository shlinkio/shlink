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
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\EventDispatcher\Event\ShortUrlCreated;
use Shlinkio\Shlink\Core\EventDispatcher\PublishingUpdatesGeneratorInterface;
use Shlinkio\Shlink\Core\EventDispatcher\RedisPubSub\NotifyNewShortUrlToRedis;
use Shlinkio\Shlink\Core\EventDispatcher\Topic;
use Throwable;

class NotifyNewShortUrlToRedisTest extends TestCase
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
        $this->createListener(false)(new ShortUrlCreated('123'));

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
        $shortUrlId = '123';
        $update = Update::forTopicAndPayload(Topic::NEW_SHORT_URL->value, []);
        $find = $this->em->find(ShortUrl::class, $shortUrlId)->willReturn(ShortUrl::withLongUrl(''));
        $generateUpdate = $this->updatesGenerator->newShortUrlUpdate(Argument::type(ShortUrl::class))->willReturn(
            $update,
        );
        $publish = $this->helper->publishUpdate($update)->willThrow($e);

        $this->createListener()(new ShortUrlCreated($shortUrlId));

        $this->logger->debug(
            'Error while trying to notify {name} with new short URL. {e}',
            ['e' => $e, 'name' => 'Redis pub/sub'],
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

    private function createListener(bool $enabled = true): NotifyNewShortUrlToRedis
    {
        return new NotifyNewShortUrlToRedis(
            $this->helper->reveal(),
            $this->updatesGenerator->reveal(),
            $this->em->reveal(),
            $this->logger->reveal(),
            $enabled,
        );
    }
}
