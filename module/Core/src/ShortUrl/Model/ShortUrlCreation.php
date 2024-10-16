<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use Cake\Chronos\Chronos;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\ShortUrl\Helper\TitleResolutionModelInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function Shlinkio\Shlink\Core\getNonEmptyOptionalValueFromInputFilter;
use function Shlinkio\Shlink\Core\getOptionalBoolFromInputFilter;
use function Shlinkio\Shlink\Core\getOptionalIntFromInputFilter;
use function Shlinkio\Shlink\Core\normalizeOptionalDate;

use const Shlinkio\Shlink\DEFAULT_SHORT_CODES_LENGTH;

final readonly class ShortUrlCreation implements TitleResolutionModelInterface
{
    /**
     * @param string[] $tags
     */
    private function __construct(
        public string $longUrl,
        public ShortUrlMode $shortUrlMode,
        public ?Chronos $validSince = null,
        public ?Chronos $validUntil = null,
        public ?string $customSlug = null,
        public ?string $pathPrefix = null,
        public ?int $maxVisits = null,
        public bool $findIfExists = false,
        public ?string $domain = null,
        public int $shortCodeLength = 5,
        public ?ApiKey $apiKey = null,
        public array $tags = [],
        public ?string $title = null,
        public bool $titleWasAutoResolved = false,
        public bool $crawlable = false,
        public bool $forwardQuery = true,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public static function fromRawData(array $data, UrlShortenerOptions $options = new UrlShortenerOptions()): self
    {
        $inputFilter = ShortUrlInputFilter::forCreation($data, $options);
        if (! $inputFilter->isValid()) {
            throw ValidationException::fromInputFilter($inputFilter);
        }

        return new self(
            longUrl: $inputFilter->getValue(ShortUrlInputFilter::LONG_URL),
            shortUrlMode: $options->mode,
            validSince: normalizeOptionalDate($inputFilter->getValue(ShortUrlInputFilter::VALID_SINCE)),
            validUntil: normalizeOptionalDate($inputFilter->getValue(ShortUrlInputFilter::VALID_UNTIL)),
            customSlug: $inputFilter->getValue(ShortUrlInputFilter::CUSTOM_SLUG),
            pathPrefix: $inputFilter->getValue(ShortUrlInputFilter::PATH_PREFIX),
            maxVisits: getOptionalIntFromInputFilter($inputFilter, ShortUrlInputFilter::MAX_VISITS),
            findIfExists: $inputFilter->getValue(ShortUrlInputFilter::FIND_IF_EXISTS) ?? false,
            domain: getNonEmptyOptionalValueFromInputFilter($inputFilter, ShortUrlInputFilter::DOMAIN),
            shortCodeLength: getOptionalIntFromInputFilter(
                $inputFilter,
                ShortUrlInputFilter::SHORT_CODE_LENGTH,
            ) ?? DEFAULT_SHORT_CODES_LENGTH,
            apiKey: $inputFilter->getValue(ShortUrlInputFilter::API_KEY),
            tags: $inputFilter->getValue(ShortUrlInputFilter::TAGS) ?? [],
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
            validSince: $this->validSince,
            validUntil: $this->validUntil,
            customSlug: $this->customSlug,
            pathPrefix: $this->pathPrefix,
            maxVisits: $this->maxVisits,
            findIfExists: $this->findIfExists,
            domain: $this->domain,
            shortCodeLength: $this->shortCodeLength,
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

    public function hasTitle(): bool
    {
        return $this->title !== null;
    }
}
