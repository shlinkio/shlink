<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher\Matomo;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\EventDispatcher\Event\UrlVisited;
use Shlinkio\Shlink\Core\EventDispatcher\Matomo\SendVisitToMatomo;
use Shlinkio\Shlink\Core\Matomo\MatomoOptions;
use Shlinkio\Shlink\Core\Matomo\MatomoVisitSenderInterface;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;

class SendVisitToMatomoTest extends TestCase
{
    private MockObject & EntityManagerInterface $em;
    private MockObject & LoggerInterface $logger;
    private MockObject & MatomoVisitSenderInterface $visitSender;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->visitSender = $this->createMock(MatomoVisitSenderInterface::class);
    }

    #[Test]
    public function visitIsNotSentWhenMatomoIsDisabled(): void
    {
        $this->em->expects($this->never())->method('find');
        $this->visitSender->expects($this->never())->method('sendVisit');
        $this->logger->expects($this->never())->method('error');
        $this->logger->expects($this->never())->method('warning');

        ($this->listener(enabled: false))(new UrlVisited('123'));
    }

    #[Test]
    public function visitIsNotSentWhenItDoesNotExist(): void
    {
        $this->em->expects($this->once())->method('find')->willReturn(null);
        $this->visitSender->expects($this->never())->method('sendVisit');
        $this->logger->expects($this->never())->method('error');
        $this->logger->expects($this->once())->method('warning')->with(
            'Tried to send visit with id "{visitId}" to matomo, but it does not exist.',
            ['visitId' => '123'],
        );

        ($this->listener())(new UrlVisited('123'));
    }

    #[Test, DataProvider('provideOriginalIpAddress')]
    public function visitIsSentWhenItExists(string|null $originalIpAddress): void
    {
        $visitId = '123';
        $visit = Visit::forBasePath(Visitor::empty());

        $this->em->expects($this->once())->method('find')->with(Visit::class, $visitId)->willReturn($visit);
        $this->visitSender->expects($this->once())->method('sendVisit')->with($visit, $originalIpAddress);
        $this->logger->expects($this->never())->method('error');
        $this->logger->expects($this->never())->method('warning');

        ($this->listener())(new UrlVisited($visitId, $originalIpAddress));
    }

    public static function provideOriginalIpAddress(): iterable
    {
        yield 'no original IP address' => [null];
        yield 'original IP address' => ['1.2.3.4'];
    }

    #[Test]
    public function logsErrorWhenTrackingFails(): void
    {
        $visitId = '123';
        $e = new Exception('Error!');

        $this->em->expects($this->once())->method('find')->with(Visit::class, $visitId)->willReturn(
            $this->createMock(Visit::class),
        );
        $this->visitSender->expects($this->once())->method('sendVisit')->willThrowException($e);
        $this->logger->expects($this->never())->method('warning');
        $this->logger->expects($this->once())->method('error')->with(
            'An error occurred while trying to send visit to Matomo. {e}',
            ['e' => $e],
        );

        ($this->listener())(new UrlVisited($visitId));
    }

    private function listener(bool $enabled = true): SendVisitToMatomo
    {
        return new SendVisitToMatomo(
            $this->em,
            $this->logger,
            new MatomoOptions(enabled: $enabled),
            $this->visitSender,
        );
    }
}
