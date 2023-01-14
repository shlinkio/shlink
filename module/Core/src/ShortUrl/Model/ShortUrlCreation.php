<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use Cake\Chronos\Chronos;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\ShortUrl\Helper\TitleResolutionModelInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function Functional\map;
use function Shlinkio\Shlink\Core\getNonEmptyOptionalValueFromInputFilter;
use function Shlinkio\Shlink\Core\getOptionalBoolFromInputFilter;
use function Shlinkio\Shlink\Core\getOptionalIntFromInputFilter;
use function Shlinkio\Shlink\Core\normalizeOptionalDate;
use function trim;

use const Shlinkio\Shlink\DEFAULT_SHORT_CODES_LENGTH;

final class ShortUrlCreation implements TitleResolutionModelInterface
{
    /**
     * @param string[] $tags
     * @param array{DeviceType, string}[] $deviceLongUrls
     */
    private function __construct(
        public readonly string $longUrl,
        public readonly array $deviceLongUrls = [],
        public readonly ?Chronos $validSince = null,
        public readonly ?Chronos $validUntil = null,
        public readonly ?string $customSlug = null,
        public readonly ?int $maxVisits = null,
        public readonly bool $findIfExists = false,
        public readonly ?string $domain = null,
        public readonly int $shortCodeLength = 5,
        public readonly bool $validateUrl = false,
        public readonly ?ApiKey $apiKey = null,
        public readonly array $tags = [],
        public readonly ?string $title = null,
        public readonly bool $titleWasAutoResolved = false,
        public readonly bool $crawlable = false,
        public readonly bool $forwardQuery = true,
    ) {
    }

    public static function createEmpty(): self
    {
        return new self('');
    }

    /**
     * @throws ValidationException
     */
    public static function fromRawData(array $data): self
    {
        $inputFilter = ShortUrlInputFilter::withRequiredLongUrl($data);
        if (! $inputFilter->isValid()) {
            throw ValidationException::fromInputFilter($inputFilter);
        }

        return new self(
            longUrl: $inputFilter->getValue(ShortUrlInputFilter::LONG_URL),
            deviceLongUrls: map(
                $inputFilter->getValue(ShortUrlInputFilter::DEVICE_LONG_URLS) ?? [],
                static fn (string $longUrl, string $deviceType) => [DeviceType::from($deviceType), trim($longUrl)],
            ),
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

    public function withResolvedTitle(string $title): self
    {
        return new self(
            $this->longUrl,
            $this->deviceLongUrls,
            $this->validSince,
            $this->validUntil,
            $this->customSlug,
            $this->maxVisits,
            $this->findIfExists,
            $this->domain,
            $this->shortCodeLength,
            $this->validateUrl,
            $this->apiKey,
            $this->tags,
            $title,
            true,
            $this->crawlable,
            $this->forwardQuery,
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

    public function doValidateUrl(): bool
    {
        return $this->validateUrl;
    }

    public function hasTitle(): bool
    {
        return $this->title !== null;
    }
}
