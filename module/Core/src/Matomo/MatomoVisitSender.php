<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Matomo;

use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifier;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;

readonly class MatomoVisitSender implements MatomoVisitSenderInterface
{
    public function __construct(
        private MatomoTrackerBuilderInterface $trackerBuilder,
        private ShortUrlStringifier $shortUrlStringifier,
    ) {
    }

    public function sendVisitToMatomo(Visit $visit, ?string $originalIpAddress = null): void
    {
        $tracker = $this->trackerBuilder->buildMatomoTracker();

        $tracker
            ->setUrl($this->resolveUrlToTrack($visit))
            ->setCustomTrackingParameter('type', $visit->type->value)
            ->setUserAgent($visit->userAgent)
            ->setUrlReferrer($visit->referer)
            ->setForceVisitDateTime($visit->date->setTimezone('UTC')->toDateTimeString());

        $location = $visit->getVisitLocation();
        if ($location !== null) {
            $tracker
                ->setCity($location->cityName)
                ->setCountry($location->countryName)
                ->setLatitude($location->latitude)
                ->setLongitude($location->longitude);
        }

        // Set not obfuscated IP if possible, as matomo handles obfuscation itself
        $ip = $originalIpAddress ?? $visit->remoteAddr;
        if ($ip !== null) {
            $tracker->setIp($ip);
        }

        if ($visit->isOrphan()) {
            $tracker->setCustomTrackingParameter('orphan', 'true');
        }

        // Send the short URL title or an empty document title to avoid different actions to be created by matomo
        $tracker->doTrackPageView($visit->shortUrl?->title() ?? '');
    }

    private function resolveUrlToTrack(Visit $visit): string
    {
        $shortUrl = $visit->shortUrl;
        if ($shortUrl === null) {
            return $visit->visitedUrl ?? '';
        }

        return $this->shortUrlStringifier->stringify($shortUrl);
    }
}
