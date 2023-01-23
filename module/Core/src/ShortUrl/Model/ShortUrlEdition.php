<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use Cake\Chronos\Chronos;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\ShortUrl\Helper\TitleResolutionModelInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;

use function array_key_exists;
use function Shlinkio\Shlink\Core\getOptionalBoolFromInputFilter;
use function Shlinkio\Shlink\Core\getOptionalIntFromInputFilter;
use function Shlinkio\Shlink\Core\normalizeOptionalDate;

final class ShortUrlEdition implements TitleResolutionModelInterface
{
    /**
     * @param string[] $tags
     * @param DeviceLongUrlPair[] $deviceLongUrls
     * @param DeviceType[] $devicesToRemove
     */
    private function __construct(
        private readonly bool $longUrlPropWasProvided = false,
        public readonly ?string $longUrl = null,
        public readonly array $deviceLongUrls = [],
        public readonly array $devicesToRemove = [],
        private readonly bool $validSincePropWasProvided = false,
        public readonly ?Chronos $validSince = null,
        private readonly bool $validUntilPropWasProvided = false,
        public readonly ?Chronos $validUntil = null,
        private readonly bool $maxVisitsPropWasProvided = false,
        public readonly ?int $maxVisits = null,
        private readonly bool $tagsPropWasProvided = false,
        public readonly array $tags = [],
        private readonly bool $titlePropWasProvided = false,
        public readonly ?string $title = null,
        public readonly bool $titleWasAutoResolved = false,
        /** @deprecated */
        public readonly bool $validateUrl = false,
        private readonly bool $crawlablePropWasProvided = false,
        public readonly bool $crawlable = false,
        private readonly bool $forwardQueryPropWasProvided = false,
        public readonly bool $forwardQuery = true,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public static function fromRawData(array $data): self
    {
        $inputFilter = ShortUrlInputFilter::withNonRequiredLongUrl($data);
        if (! $inputFilter->isValid()) {
            throw ValidationException::fromInputFilter($inputFilter);
        }

        [$deviceLongUrls, $devicesToRemove] = DeviceLongUrlPair::fromMapToChangeSet(
            $inputFilter->getValue(ShortUrlInputFilter::DEVICE_LONG_URLS) ?? [],
        );

        return new self(
            longUrlPropWasProvided: array_key_exists(ShortUrlInputFilter::LONG_URL, $data),
            longUrl: $inputFilter->getValue(ShortUrlInputFilter::LONG_URL),
            deviceLongUrls: $deviceLongUrls,
            devicesToRemove: $devicesToRemove,
            validSincePropWasProvided: array_key_exists(ShortUrlInputFilter::VALID_SINCE, $data),
            validSince: normalizeOptionalDate($inputFilter->getValue(ShortUrlInputFilter::VALID_SINCE)),
            validUntilPropWasProvided: array_key_exists(ShortUrlInputFilter::VALID_UNTIL, $data),
            validUntil: normalizeOptionalDate($inputFilter->getValue(ShortUrlInputFilter::VALID_UNTIL)),
            maxVisitsPropWasProvided: array_key_exists(ShortUrlInputFilter::MAX_VISITS, $data),
            maxVisits: getOptionalIntFromInputFilter($inputFilter, ShortUrlInputFilter::MAX_VISITS),
            tagsPropWasProvided: array_key_exists(ShortUrlInputFilter::TAGS, $data),
            tags: $inputFilter->getValue(ShortUrlInputFilter::TAGS),
            titlePropWasProvided: array_key_exists(ShortUrlInputFilter::TITLE, $data),
            title: $inputFilter->getValue(ShortUrlInputFilter::TITLE),
            validateUrl: getOptionalBoolFromInputFilter($inputFilter, ShortUrlInputFilter::VALIDATE_URL) ?? false,
            crawlablePropWasProvided: array_key_exists(ShortUrlInputFilter::CRAWLABLE, $data),
            crawlable: $inputFilter->getValue(ShortUrlInputFilter::CRAWLABLE),
            forwardQueryPropWasProvided: array_key_exists(ShortUrlInputFilter::FORWARD_QUERY, $data),
            forwardQuery: getOptionalBoolFromInputFilter($inputFilter, ShortUrlInputFilter::FORWARD_QUERY) ?? true,
        );
    }

    public function withResolvedTitle(string $title): static
    {
        return new self(
            longUrlPropWasProvided: $this->longUrlPropWasProvided,
            longUrl: $this->longUrl,
            deviceLongUrls: $this->deviceLongUrls,
            devicesToRemove: $this->devicesToRemove,
            validSincePropWasProvided: $this->validSincePropWasProvided,
            validSince: $this->validSince,
            validUntilPropWasProvided: $this->validUntilPropWasProvided,
            validUntil: $this->validUntil,
            maxVisitsPropWasProvided: $this->maxVisitsPropWasProvided,
            maxVisits: $this->maxVisits,
            tagsPropWasProvided: $this->tagsPropWasProvided,
            tags: $this->tags,
            titlePropWasProvided: $this->titlePropWasProvided,
            title: $title,
            titleWasAutoResolved: true,
            validateUrl: $this->validateUrl,
            crawlablePropWasProvided: $this->crawlablePropWasProvided,
            crawlable: $this->crawlable,
            forwardQueryPropWasProvided: $this->forwardQueryPropWasProvided,
            forwardQuery: $this->forwardQuery,
        );
    }

    public function getLongUrl(): string
    {
        return $this->longUrl ?? '';
    }

    public function longUrlWasProvided(): bool
    {
        return $this->longUrlPropWasProvided && $this->longUrl !== null;
    }

    public function validSinceWasProvided(): bool
    {
        return $this->validSincePropWasProvided;
    }

    public function validUntilWasProvided(): bool
    {
        return $this->validUntilPropWasProvided;
    }

    public function maxVisitsWasProvided(): bool
    {
        return $this->maxVisitsPropWasProvided;
    }

    public function tagsWereProvided(): bool
    {
        return $this->tagsPropWasProvided;
    }

    public function titleWasProvided(): bool
    {
        return $this->titlePropWasProvided;
    }

    public function hasTitle(): bool
    {
        return $this->titleWasProvided();
    }

    public function titleWasAutoResolved(): bool
    {
        return $this->titleWasAutoResolved;
    }

    /** @deprecated */
    public function doValidateUrl(): bool
    {
        return $this->validateUrl;
    }

    public function crawlableWasProvided(): bool
    {
        return $this->crawlablePropWasProvided;
    }

    public function forwardQueryWasProvided(): bool
    {
        return $this->forwardQueryPropWasProvided;
    }
}
