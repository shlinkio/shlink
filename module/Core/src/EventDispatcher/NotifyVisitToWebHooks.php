<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher;

use Doctrine\ORM\EntityManagerInterface;
use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Transformer\ShortUrlDataTransformer;
use Throwable;

use function Functional\map;
use function Functional\partial_left;
use function GuzzleHttp\Promise\settle;

class NotifyVisitToWebHooks
{
    /** @var ClientInterface */
    private $httpClient;
    /** @var EntityManagerInterface */
    private $em;
    /** @var LoggerInterface */
    private $logger;
    /** @var array */
    private $webhooks;
    /** @var ShortUrlDataTransformer */
    private $transformer;

    public function __construct(
        ClientInterface $httpClient,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        array $webhooks,
        array $domainConfig
    ) {
        $this->httpClient = $httpClient;
        $this->em = $em;
        $this->logger = $logger;
        $this->webhooks = $webhooks;
        $this->transformer = new ShortUrlDataTransformer($domainConfig);
    }

    public function __invoke(ShortUrlLocated $shortUrlLocated): void
    {
        $visitId = $shortUrlLocated->visitId();

        /** @var Visit|null $visit */
        $visit = $this->em->find(Visit::class, $visitId);
        if ($visit === null) {
            $this->logger->warning('Tried to notify webhooks for visit with id "{visitId}", but it does not exist.', [
                'visitId' => $visitId,
            ]);
            return;
        }

        $requestOptions = [
            RequestOptions::TIMEOUT => 10,
            RequestOptions::JSON => [
                'shortUrl' => $this->transformer->transform($visit->getShortUrl(), false),
                'visit' => $visit->jsonSerialize(),
            ],
        ];
        $logWebhookWarning = function (string $webhook, Throwable $e) use ($visitId): void {
            $this->logger->warning('Failed to notify visit with id "{visitId}" to webhook "{webhook}". {e}', [
                'visitId' => $visitId,
                'webhook' => $webhook,
                'e' => $e,
            ]);
        };

        $requestPromises = map($this->webhooks, function (string $webhook) use ($requestOptions, $logWebhookWarning) {
            $promise = $this->httpClient->requestAsync(RequestMethodInterface::METHOD_POST, $webhook, $requestOptions);
            return $promise->otherwise(partial_left($logWebhookWarning, $webhook));
        });

        // Wait for all the promises to finish, ignoring rejections, as in those cases we only want to log the error.
        settle($requestPromises)->wait();
    }
}
