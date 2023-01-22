<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\EventDispatcher\Event\VisitLocated;
use Shlinkio\Shlink\Core\EventDispatcher\NotifyVisitToWebHooks;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Shlinkio\Shlink\Core\Options\WebhookOptions;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifier;
use Shlinkio\Shlink\Core\ShortUrl\Transformer\ShortUrlDataTransformer;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;

use function count;
use function Functional\contains;

class NotifyVisitToWebHooksTest extends TestCase
{
    private MockObject & ClientInterface $httpClient;
    private MockObject & EntityManagerInterface $em;
    private MockObject & LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    /** @test */
    public function emptyWebhooksMakeNoFurtherActions(): void
    {
        $this->em->expects($this->never())->method('find');

        $this->createListener([])(new VisitLocated('1'));
    }

    /** @test */
    public function invalidVisitDoesNotPerformAnyRequest(): void
    {
        $this->em->expects($this->once())->method('find')->with(Visit::class, '1')->willReturn(null);
        $this->httpClient->expects($this->never())->method('requestAsync');
        $this->logger->expects($this->once())->method('warning')->with(
            'Tried to notify webhooks for visit with id "{visitId}", but it does not exist.',
            ['visitId' => '1'],
        );

        $this->createListener(['foo', 'bar'])(new VisitLocated('1'));
    }

    /** @test */
    public function orphanVisitDoesNotPerformAnyRequestWhenDisabled(): void
    {
        $this->em->expects($this->once())->method('find')->with(Visit::class, '1')->willReturn(
            Visit::forBasePath(Visitor::emptyInstance()),
        );
        $this->httpClient->expects($this->never())->method('requestAsync');
        $this->logger->expects($this->never())->method('warning');

        $this->createListener(['foo', 'bar'], false)(new VisitLocated('1'));
    }

    /**
     * @test
     * @dataProvider provideVisits
     */
    public function expectedRequestsArePerformedToWebhooks(Visit $visit, array $expectedResponseKeys): void
    {
        $webhooks = ['foo', 'invalid', 'bar', 'baz'];
        $invalidWebhooks = ['invalid', 'baz'];

        $this->em->expects($this->once())->method('find')->with(Visit::class, '1')->willReturn($visit);
        $this->httpClient->expects($this->exactly(count($webhooks)))->method('requestAsync')->with(
            RequestMethodInterface::METHOD_POST,
            $this->istype('string'),
            $this->callback(function (array $requestOptions) use ($expectedResponseKeys) {
                Assert::assertArrayHasKey(RequestOptions::HEADERS, $requestOptions);
                Assert::assertArrayHasKey(RequestOptions::JSON, $requestOptions);
                Assert::assertArrayHasKey(RequestOptions::TIMEOUT, $requestOptions);
                Assert::assertEquals(10, $requestOptions[RequestOptions::TIMEOUT]);
                Assert::assertEquals(['User-Agent' => 'Shlink:v1.2.3'], $requestOptions[RequestOptions::HEADERS]);

                $json = $requestOptions[RequestOptions::JSON];
                Assert::assertCount(count($expectedResponseKeys), $json);
                foreach ($expectedResponseKeys as $key) {
                    Assert::assertArrayHasKey($key, $json);
                }

                return true;
            }),
        )->willReturnCallback(function ($_, $webhook) use ($invalidWebhooks) {
            $shouldReject = contains($invalidWebhooks, $webhook);
            return $shouldReject ? new RejectedPromise(new Exception('')) : new FulfilledPromise('');
        });
        $this->logger->expects($this->exactly(count($invalidWebhooks)))->method('warning')->with(
            'Failed to notify visit with id "{visitId}" to webhook "{webhook}". {e}',
            $this->callback(function (array $extra): bool {
                Assert::assertArrayHasKey('webhook', $extra);
                Assert::assertArrayHasKey('visitId', $extra);
                Assert::assertArrayHasKey('e', $extra);

                return true;
            }),
        );

        $this->createListener($webhooks)(new VisitLocated('1'));
    }

    public function provideVisits(): iterable
    {
        yield 'regular visit' => [
            Visit::forValidShortUrl(ShortUrl::createFake(), Visitor::emptyInstance()),
            ['shortUrl', 'visit'],
        ];
        yield 'orphan visit' => [Visit::forBasePath(Visitor::emptyInstance()), ['visit'],];
    }

    private function createListener(array $webhooks, bool $notifyOrphanVisits = true): NotifyVisitToWebHooks
    {
        return new NotifyVisitToWebHooks(
            $this->httpClient,
            $this->em,
            $this->logger,
            new WebhookOptions(
                ['webhooks' => $webhooks, 'notify_orphan_visits_to_webhooks' => $notifyOrphanVisits],
            ),
            new ShortUrlDataTransformer(new ShortUrlStringifier([])),
            new AppOptions('Shlink', '1.2.3'),
        );
    }
}
