<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\EventDispatcher\Event\VisitLocated;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Shlinkio\Shlink\Core\Transformer\ShortUrlDataTransformer;
use Throwable;

use function Functional\map;
use function Functional\partial_left;
use function GuzzleHttp\Promise\settle;

class NotifyVisitToWebHooks
{
    private ClientInterface $httpClient;
    private EntityManagerInterface $em;
    private LoggerInterface $logger;
    /** @var string[] */
    private array $webhooks;
    private ShortUrlDataTransformer $transformer;
    private AppOptions $appOptions;

    public function __construct(
        ClientInterface $httpClient,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        array $webhooks,
        array $domainConfig,
        AppOptions $appOptions
    ) {
        $this->httpClient = $httpClient;
        $this->em = $em;
        $this->logger = $logger;
        $this->webhooks = $webhooks;
        $this->transformer = new ShortUrlDataTransformer($domainConfig);
        $this->appOptions = $appOptions;
    }

    public function __invoke(VisitLocated $shortUrlLocated): void
    {
        if (empty($this->webhooks)) {
            return;
        }

        $visitId = $shortUrlLocated->visitId();

        /** @var Visit|null $visit */
        $visit = $this->em->find(Visit::class, $visitId);
        if ($visit === null) {
            $this->logger->warning('Tried to notify webhooks for visit with id "{visitId}", but it does not exist.', [
                'visitId' => $visitId,
            ]);
            return;
        }

        $requestOptions = $this->buildRequestOptions($visit);
        $requestPromises = $this->performRequests($requestOptions, $visitId);

        // Wait for all the promises to finish, ignoring rejections, as in those cases we only want to log the error.
        settle($requestPromises)->wait();
    }

    private function buildRequestOptions(Visit $visit): array
    {
        return [
            RequestOptions::TIMEOUT => 10,
            RequestOptions::HEADERS => [
                'User-Agent' => (string) $this->appOptions,
            ],
            RequestOptions::JSON => [
                'shortUrl' => $this->transformer->transform($visit->getShortUrl()),
                'visit' => $visit->jsonSerialize(),
            ],
        ];
    }

    /**
     * @param Promise[] $requestOptions
     */
    private function performRequests(array $requestOptions, string $visitId): array
    {
        $logWebhookFailure = Closure::fromCallable([$this, 'logWebhookFailure']);

        return map(
            $this->webhooks,
            fn (string $webhook): PromiseInterface => $this->httpClient
                ->requestAsync(RequestMethodInterface::METHOD_POST, $webhook, $requestOptions)
                ->otherwise(partial_left($logWebhookFailure, $webhook, $visitId)),
        );
    }

    private function logWebhookFailure(string $webhook, string $visitId, Throwable $e): void
    {
        $this->logger->warning('Failed to notify visit with id "{visitId}" to webhook "{webhook}". {e}', [
            'visitId' => $visitId,
            'webhook' => $webhook,
            'e' => $e,
        ]);
    }
}
