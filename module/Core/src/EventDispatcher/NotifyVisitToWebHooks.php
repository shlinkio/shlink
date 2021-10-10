<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher;

use Doctrine\ORM\EntityManagerInterface;
use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\EventDispatcher\Event\VisitLocated;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Shlinkio\Shlink\Core\Options\WebhookOptions;
use Throwable;

use function Functional\map;

class NotifyVisitToWebHooks
{
    public function __construct(
        private ClientInterface $httpClient,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private WebhookOptions $webhookOptions,
        private DataTransformerInterface $transformer,
        private AppOptions $appOptions,
    ) {
    }

    public function __invoke(VisitLocated $shortUrlLocated): void
    {
        if (! $this->webhookOptions->hasWebhooks()) {
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

        if ($visit->isOrphan() && ! $this->webhookOptions->notifyOrphanVisits()) {
            return;
        }

        $requestOptions = $this->buildRequestOptions($visit);
        $requestPromises = $this->performRequests($requestOptions, $visitId);

        // Wait for all the promises to finish, ignoring rejections, as in those cases we only want to log the error.
        Utils::settle($requestPromises)->wait();
    }

    private function buildRequestOptions(Visit $visit): array
    {
        $payload = ['visit' => $visit->jsonSerialize()];
        $shortUrl = $visit->getShortUrl();
        if ($shortUrl !== null) {
            $payload['shortUrl'] = $this->transformer->transform($shortUrl);
        }

        return [
            RequestOptions::TIMEOUT => 10,
            RequestOptions::JSON => $payload,
            RequestOptions::HEADERS => ['User-Agent' => $this->appOptions->__toString()],
        ];
    }

    /**
     * @param Promise[] $requestOptions
     */
    private function performRequests(array $requestOptions, string $visitId): array
    {
        return map(
            $this->webhookOptions->webhooks(),
            fn (string $webhook): PromiseInterface => $this->httpClient
                ->requestAsync(RequestMethodInterface::METHOD_POST, $webhook, $requestOptions)
                ->otherwise(fn (Throwable $e) => $this->logWebhookFailure($webhook, $visitId, $e)),
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
