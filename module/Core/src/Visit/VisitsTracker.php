<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

use Doctrine\ORM;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\EventDispatcher\Event\UrlVisited;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;

class VisitsTracker implements VisitsTrackerInterface
{
    private ORM\EntityManagerInterface $em;
    private EventDispatcherInterface $eventDispatcher;
    private UrlShortenerOptions $options;

    public function __construct(
        ORM\EntityManagerInterface $em,
        EventDispatcherInterface $eventDispatcher,
        UrlShortenerOptions $options
    ) {
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
        $this->options = $options;
    }

    public function track(ShortUrl $shortUrl, Visitor $visitor): void
    {
        $this->trackVisit(
            Visit::forValidShortUrl($shortUrl, $visitor, $this->options->anonymizeRemoteAddr()),
            $visitor,
        );
    }

    public function trackInvalidShortUrlVisit(Visitor $visitor): void
    {
        if (! $this->options->trackOrphanVisits()) {
            return;
        }

        $this->trackVisit(Visit::forInvalidShortUrl($visitor, $this->options->anonymizeRemoteAddr()), $visitor);
    }

    public function trackBaseUrlVisit(Visitor $visitor): void
    {
        if (! $this->options->trackOrphanVisits()) {
            return;
        }

        $this->trackVisit(Visit::forBasePath($visitor, $this->options->anonymizeRemoteAddr()), $visitor);
    }

    public function trackRegularNotFoundVisit(Visitor $visitor): void
    {
        if (! $this->options->trackOrphanVisits()) {
            return;
        }

        $this->trackVisit(Visit::forRegularNotFound($visitor, $this->options->anonymizeRemoteAddr()), $visitor);
    }

    private function trackVisit(Visit $visit, Visitor $visitor): void
    {
        $this->em->transactional(function () use ($visit, $visitor): void {
            $this->em->persist($visit);
            $this->em->flush();

            $this->eventDispatcher->dispatch(new UrlVisited($visit->getId(), $visitor->getRemoteAddress()));
        });
    }
}
