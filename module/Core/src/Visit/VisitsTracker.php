<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

use Doctrine\ORM;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\EventDispatcher\Event\ShortUrlVisited;
use Shlinkio\Shlink\Core\Model\Visitor;

class VisitsTracker implements VisitsTrackerInterface
{
    private ORM\EntityManagerInterface $em;
    private EventDispatcherInterface $eventDispatcher;
    private bool $anonymizeRemoteAddr;

    public function __construct(
        ORM\EntityManagerInterface $em,
        EventDispatcherInterface $eventDispatcher,
        bool $anonymizeRemoteAddr
    ) {
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
        $this->anonymizeRemoteAddr = $anonymizeRemoteAddr;
    }

    public function track(ShortUrl $shortUrl, Visitor $visitor): void
    {
        $visit = Visit::forValidShortUrl($shortUrl, $visitor, $this->anonymizeRemoteAddr);

        $this->em->persist($visit);
        $this->em->flush();

        $this->eventDispatcher->dispatch(new ShortUrlVisited($visit->getId(), $visitor->getRemoteAddress()));
    }
}
