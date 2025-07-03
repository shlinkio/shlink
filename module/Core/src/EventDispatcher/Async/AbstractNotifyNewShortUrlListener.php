<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher\Async;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\UpdatePublishing\PublishingHelperInterface;
use Shlinkio\Shlink\Core\Config\Options\RealTimeUpdatesOptions;
use Shlinkio\Shlink\Core\EventDispatcher\Event\ShortUrlCreated;
use Shlinkio\Shlink\Core\EventDispatcher\PublishingUpdatesGeneratorInterface;
use Shlinkio\Shlink\Core\EventDispatcher\Topic;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Throwable;

abstract class AbstractNotifyNewShortUrlListener extends AbstractAsyncListener
{
    public function __construct(
        private readonly PublishingHelperInterface $publishingHelper,
        private readonly PublishingUpdatesGeneratorInterface $updatesGenerator,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        private readonly RealTimeUpdatesOptions $realTimeUpdatesOptions,
    ) {
    }

    public function __invoke(ShortUrlCreated $shortUrlCreated): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $shortUrlId = $shortUrlCreated->shortUrlId;
        $shortUrl = $this->em->find(ShortUrl::class, $shortUrlId);
        $name = $this->getRemoteSystem()->value;

        if ($shortUrl === null) {
            $this->logger->warning(
                'Tried to notify {name} for new short URL with id "{shortUrlId}", but it does not exist.',
                ['shortUrlId' => $shortUrlId, 'name' => $name],
            );
            return;
        }

        if (! $this->realTimeUpdatesOptions->isTopicEnabled(Topic::NEW_SHORT_URL)) {
            return;
        }

        try {
            $this->publishingHelper->publishUpdate($this->updatesGenerator->newShortUrlUpdate($shortUrl));
        } catch (Throwable $e) {
            $this->logger->debug(
                'Error while trying to notify {name} with new short URL. {e}',
                ['e' => $e, 'name' => $name],
            );
        }
    }
}
