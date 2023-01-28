<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use Cake\Chronos\Chronos;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Helper\TitleResolutionModelInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function Shlinkio\Shlink\Core\getNonEmptyOptionalValueFromInputFilter;
use function Shlinkio\Shlink\Core\getOptionalBoolFromInputFilter;
use function Shlinkio\Shlink\Core\getOptionalIntFromInputFilter;
use function Shlinkio\Shlink\Core\normalizeOptionalDate;

use const Shlinkio\Shlink\DEFAULT_SHORT_CODES_LENGTH;

final class ShortUrlCreation implements TitleResolutionModelInterface
{
    /**
     * @param string[] $tags
     * @param DeviceLongUrlPair[] $deviceLongUrls
     */
    private function __construct(
        public readonly string $longUrl,
        public readonly ShortUrlMode $shortUrlMode,
        public readonly array $deviceLongUrls = [],
        public readonly ?Chronos $validSince = null,
        public readonly ?Chronos $validUntil = null,
        public readonly ?string $customSlug = null,
        public readonly ?int $maxVisits = null,
        public readonly bool $findIfExists = false,
        public readonly ?string $domain = null,
        public readonly int $shortCodeLength = 5,
        /** @deprecated  */
        public readonly bool $validateUrl = false,
        public readonly ?ApiKey $apiKey = null,
        public readonly array $tags = [],
        public readonly ?string $title = null,
        public readonly bool $titleWasAutoResolved = false,
        public readonly bool $crawlable = false,
        public readonly bool $forwardQuery = true,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public static function fromRawData(array $data, ?UrlShortenerOptions $options = null): self
    {
        $options = $options ?? new UrlShortenerOptions();
        $inputFilter = ShortUrlInputFilter::withRequiredLongUrl($data, $options);
        if (! $inputFilter->isValid()) {
            throw ValidationException::fromInputFilter($inputFilter);
        }

        [$deviceLongUrls] = DeviceLongUrlPair::fromMapToChangeSet(
            $inputFilter->getValue(ShortUrlInputFilter::DEVICE_LONG_URLS) ?? [],
        );

        return new self(
            longUrl: $inputFilter->getValue(ShortUrlInputFilter::LONG_URL),
            shortUrlMode: $options->mode,
            deviceLongUrls: $deviceLongUrls,
            validSince: normalizeOptionalDate($inputFilter->getValue(ShortUrlInputFilter::VALID_SINCE)),
            validUntil: normalizeOptionalDate($inputFilter->getValue(ShortUrlInputFilter::VALID_UNTIL)),
            customSlug: $inputFilter->getValue(ShortUrlInputFilter::CUSTOM_SLUG),
            maxVisits: getOptionalIntFromInputFilter($inputFilter, ShortUrlInputFilter::MAX_VISITS),
            findIfExists: $inputFilter->getValue(ShortUrlInputFilter::FIND_IF_EXISTS) ?? false,
            domain: getNonEmptyOptionalValueFromInputFilter($inputFilter, ShortUrlInputFilter::DOMAIN),
            shortCodeLength: getOptionalIntFromInputFilter(
                $inputFilter,
                ShortUrlInputFilter::SHORT_CODE_LENGTH,
            ) ?? DEFAULT_SHORT_CODES_LENGTH,
            validateUrl: getOptionalBoolFromInputFilter($inputFilter, ShortUrlInputFilter::VALIDATE_URL) ?? false,
            apiKey: $inputFilter->getValue(ShortUrlInputFilter::API_KEY),
            tags: $inputFilter->getValue(ShortUrlInputFilter::TAGS),
            title: $inputFilter->getValue(ShortUrlInputFilter::TITLE),
            crawlable: $inputFilter->getValue(ShortUrlInputFilter::CRAWLABLE),
            forwardQuery: getOptionalBoolFromInputFilter($inputFilter, ShortUrlInputFilter::FORWARD_QUERY) ?? true,
        );
    }

    public function withResolvedTitle(string $title): static
    {
        return new self(
            longUrl: $this->longUrl,
            shortUrlMode: $this->shortUrlMode,
            deviceLongUrls: $this->deviceLongUrls,
            validSince: $this->validSince,
            validUntil: $this->validUntil,
            customSlug: $this->customSlug,
            maxVisits: $this->maxVisits,
            findIfExists: $this->findIfExists,
            domain: $this->domain,
            shortCodeLength: $this->shortCodeLength,
            validateUrl: $this->validateUrl,
            apiKey: $this->apiKey,
            tags: $this->tags,
            title: $title,
            titleWasAutoResolved: true,
            crawlable: $this->crawlable,
            forwardQuery: $this->forwardQuery,
        );
    }

    public function getLongUrl(): string
    {
        return $this->longUrl;
    }

    public function hasValidSince(): bool
    {
        return $this->validSince !== null;
    }

    public function hasValidUntil(): bool
    {
        return $this->validUntil !== null;
    }

    public function hasCustomSlug(): bool
    {
        return $this->customSlug !== null;
    }

    public function hasMaxVisits(): bool
    {
        return $this->maxVisits !== null;
    }

    public function hasDomain(): bool
    {
        return $this->domain !== null;
    }

    /** @deprecated  */
    public function doValidateUrl(): bool
    {
        return $this->validateUrl;
    }

    public function hasTitle(): bool
    {
        return $this->title !== null;
    }
}
