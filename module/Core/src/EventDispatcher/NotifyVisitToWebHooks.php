<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher;

use Doctrine\ORM\EntityManagerInterface;
use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Entity\Visit;

use function Functional\map;

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

    public function __construct(
        ClientInterface $httpClient,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        array $webhooks
    ) {
        $this->httpClient = $httpClient;
        $this->em = $em;
        $this->logger = $logger;
        $this->webhooks = $webhooks;
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
            RequestOptions::JSON => $visit->jsonSerialize(),
        ];
        $requestPromises = map($this->webhooks, function (string $webhook) use ($requestOptions, $visitId) {
            $promise = $this->httpClient->requestAsync(RequestMethodInterface::METHOD_POST, $webhook, $requestOptions);
            return $promise->otherwise(function () use ($webhook, $visitId) {
                // Log failures
                $this->logger->warning('Failed to notify visit with id "{visitId}" to "{webhook}" webhook', [
                    'visitId' => $visitId,
                    'webhook' => $webhook,
                ]);
            });
        });
    }
}
