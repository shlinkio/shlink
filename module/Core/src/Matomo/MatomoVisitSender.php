<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Matomo;

use Override;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Matomo\Model\SendVisitsResult;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifier;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Repository\VisitIterationRepositoryInterface;
use Throwable;

use function strtolower;

readonly class MatomoVisitSender implements MatomoVisitSenderInterface
{
    public function __construct(
        private MatomoTrackerBuilderInterface $trackerBuilder,
        private MatomoOptions $matomoOptions,
        private ShortUrlStringifier $shortUrlStringifier,
        private VisitIterationRepositoryInterface $visitIterationRepository,
    ) {}

    /**
     * Sends all visits in provided date range to matomo, and returns the amount of affected visits
     */
    #[Override]
    public function sendVisitsInDateRange(
        DateRange $dateRange,
        VisitSendingProgressTrackerInterface|null $progressTracker = null,
    ): SendVisitsResult {
        $visitsIterator = $this->visitIterationRepository->findAllVisits($dateRange);
        $successfulVisits = 0;
        $failedVisits = 0;

        foreach ($visitsIterator as $index => $visit) {
            try {
                $this->sendVisit($visit);
                $progressTracker?->success($index);
                $successfulVisits++;
            } catch (Throwable $e) {
                $progressTracker?->error($index, $e);
                $failedVisits++;
            }
        }

        return new SendVisitsResult($successfulVisits, $failedVisits);
    }

    #[Override]
    public function sendVisit(Visit $visit, string|null $originalIpAddress = null): void
    {
        $tracker = $this->trackerBuilder->buildMatomoTracker();

        $tracker
            ->setUrl($this->resolveUrlToTrack($visit))
            ->setUserAgent($visit->userAgent)
            ->setUrlReferrer($visit->referer)
            ->setForceVisitDateTime($visit->date->setTimezone('UTC')->toDateTimeString());

        // Track custom information if it has been configured
        if ($this->matomoOptions->visitTypeDimensionId !== null) {
            $tracker->setCustomDimension($this->matomoOptions->visitTypeDimensionId, $visit->type->value);
        }
        if ($this->matomoOptions->isOrphanDimensionId !== null) {
            $tracker->setCustomDimension(
                $this->matomoOptions->isOrphanDimensionId,
                $visit->isOrphan() ? 'true' : 'false',
            );
        }
        if ($this->matomoOptions->visitDomainDimensionId !== null) {
            $tracker->setCustomDimension(
                $this->matomoOptions->visitDomainDimensionId,
                $this->resolveDomainToTrack($visit),
            );
        }

        $location = $visit->visitLocation;
        if ($location !== null) {
            $tracker
                ->setCity($location->cityName)
                ->setCountry(strtolower($location->countryCode))
                ->setLatitude($location->latitude)
                ->setLongitude($location->longitude);
        }

        // Set not obfuscated IP if possible, as matomo handles obfuscation itself
        $ip = $originalIpAddress ?? $visit->remoteAddr;
        if ($ip !== null) {
            $tracker->setIp($ip);
        }

        // Send the short URL title or an empty document title to avoid different actions to be created by matomo
        $tracker->doTrackPageView($visit->shortUrl->title ?? '');
    }

    private function resolveUrlToTrack(Visit $visit): string
    {
        $shortUrl = $visit->shortUrl;
        if ($shortUrl === null) {
            return $visit->visitedUrl ?? '';
        }

        return $this->shortUrlStringifier->stringify($shortUrl);
    }

    private function resolveDomainToTrack(Visit $visit): string
    {
        $shortUrl = $visit->shortUrl;
        return $this->shortUrlStringifier->domain($shortUrl ?? ShortUrlIdentifier::fromShortCodeAndDomain(''));
    }
}
