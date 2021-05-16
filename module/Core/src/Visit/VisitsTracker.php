<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

use Doctrine\ORM;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\EventDispatcher\Event\UrlVisited;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Options\TrackingOptions;

class VisitsTracker implements VisitsTrackerInterface
{
    private ORM\EntityManagerInterface $em;
    private EventDispatcherInterface $eventDispatcher;
    private TrackingOptions $options;

    public function __construct(
        ORM\EntityManagerInterface $em,
        EventDispatcherInterface $eventDispatcher,
        TrackingOptions $options
    ) {
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
        $this->options = $options;
    }

    public function track(ShortUrl $shortUrl, Visitor $visitor): void
    {
        $this->trackVisit(
            Visit::forValidShortUrl($shortUrl, $visitor, $this->options->anonymizeRemoteAddr()),
            $visitor->normalizeForTrackingOptions($this->options),
        );
    }

    public function trackInvalidShortUrlVisit(Visitor $visitor): void
    {
        $this->trackOrphanVisit(
            Visit::forInvalidShortUrl($visitor, $this->options->anonymizeRemoteAddr()),
            $visitor->normalizeForTrackingOptions($this->options),
        );
    }

    public function trackBaseUrlVisit(Visitor $visitor): void
    {
        $this->trackOrphanVisit(
            Visit::forBasePath($visitor, $this->options->anonymizeRemoteAddr()),
            $visitor->normalizeForTrackingOptions($this->options),
        );
    }

    public function trackRegularNotFoundVisit(Visitor $visitor): void
    {
        $this->trackOrphanVisit(
            Visit::forRegularNotFound($visitor, $this->options->anonymizeRemoteAddr()),
            $visitor->normalizeForTrackingOptions($this->options),
        );
    }

    private function trackOrphanVisit(Visit $visit, Visitor $visitor): void
    {
        if (! $this->options->trackOrphanVisits()) {
            return;
        }

        $this->trackVisit($visit, $visitor);
    }

    private function trackVisit(Visit $visit, Visitor $visitor): void
    {
        if ($this->options->disableTracking()) {
            return;
        }

        $this->em->transactional(function () use ($visit, $visitor): void {
            $this->em->persist($visit);
            $this->em->flush();

            $this->eventDispatcher->dispatch(new UrlVisited($visit->getId(), $visitor->getRemoteAddress()));
        });
    }
}
