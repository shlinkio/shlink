<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\Async;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\UpdatePublishing\PublishingHelperInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\EventDispatcher\Event\ShortUrlCreated;
use Shlinkio\Shlink\Core\EventDispatcher\PublishingUpdatesGeneratorInterface;
use Throwable;

abstract class AbstractNotifyNewShortUrlListener
{
    public function __construct(
        private readonly PublishingHelperInterface $mercureHelper,
        private readonly PublishingUpdatesGeneratorInterface $updatesGenerator,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ShortUrlCreated $shortUrlCreated): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $shortUrlId = $shortUrlCreated->shortUrlId;
        $shortUrl = $this->em->find(ShortUrl::class, $shortUrlId);
        $name = $this->getRemoteSystemName();

        if ($shortUrl === null) {
            $this->logger->warning(
                'Tried to notify {name} for new short URL with id "{shortUrlId}", but it does not exist.',
                ['shortUrlId' => $shortUrlId, 'name' => $name],
            );
            return;
        }

        try {
            $this->mercureHelper->publishUpdate($this->updatesGenerator->newShortUrlUpdate($shortUrl));
        } catch (Throwable $e) {
            $this->logger->debug(
                'Error while trying to notify {name} with new short URL. {e}',
                ['e' => $e, 'name' => $name],
            );
        }
    }

    abstract protected function isEnabled(): bool;

    abstract protected function getRemoteSystemName(): string;
}
