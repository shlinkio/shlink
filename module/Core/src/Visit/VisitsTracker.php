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
    public function __construct(
        private ORM\EntityManagerInterface $em,
        private EventDispatcherInterface $eventDispatcher,
        private TrackingOptions $options,
    ) {
    }

    public function track(ShortUrl $shortUrl, Visitor $visitor): void
    {
        $this->trackVisit(
            fn (Visitor $v) => Visit::forValidShortUrl($shortUrl, $v, $this->options->anonymizeRemoteAddr()),
            $visitor,
        );
    }

    public function trackInvalidShortUrlVisit(Visitor $visitor): void
    {
        $this->trackOrphanVisit(
            fn (Visitor $v) => Visit::forInvalidShortUrl($v, $this->options->anonymizeRemoteAddr()),
            $visitor,
        );
    }

    public function trackBaseUrlVisit(Visitor $visitor): void
    {
        $this->trackOrphanVisit(
            fn (Visitor $v) => Visit::forBasePath($v, $this->options->anonymizeRemoteAddr()),
            $visitor,
        );
    }

    public function trackRegularNotFoundVisit(Visitor $visitor): void
    {
        $this->trackOrphanVisit(
            fn (Visitor $v) => Visit::forRegularNotFound($v, $this->options->anonymizeRemoteAddr()),
            $visitor,
        );
    }

    private function trackOrphanVisit(callable $createVisit, Visitor $visitor): void
    {
        if (! $this->options->trackOrphanVisits()) {
            return;
        }

        $this->trackVisit($createVisit, $visitor);
    }

    private function trackVisit(callable $createVisit, Visitor $visitor): void
    {
        if ($this->options->disableTracking()) {
            return;
        }

        $visit = $createVisit($visitor->normalizeForTrackingOptions($this->options));
        $this->em->persist($visit);
        $this->em->flush();

        $this->eventDispatcher->dispatch(new UrlVisited($visit->getId(), $visitor->getRemoteAddress()));
    }
}
