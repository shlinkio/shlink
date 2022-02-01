<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Cake\Chronos\Chronos;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\ShortUrl\Helper\TitleResolutionModelInterface;
use Shlinkio\Shlink\Core\Validation\ShortUrlInputFilter;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function Shlinkio\Shlink\Core\getOptionalBoolFromInputFilter;
use function Shlinkio\Shlink\Core\getOptionalIntFromInputFilter;
use function Shlinkio\Shlink\Core\parseDateField;

use const Shlinkio\Shlink\DEFAULT_SHORT_CODES_LENGTH;

final class ShortUrlMeta implements TitleResolutionModelInterface
{
    private string $longUrl;
    private ?Chronos $validSince = null;
    private ?Chronos $validUntil = null;
    private ?string $customSlug = null;
    private ?int $maxVisits = null;
    private ?bool $findIfExists = null;
    private ?string $domain = null;
    private int $shortCodeLength = 5;
    private bool $validateUrl = false;
    private ?ApiKey $apiKey = null;
    private array $tags = [];
    private ?string $title = null;
    private bool $titleWasAutoResolved = false;
    private bool $crawlable = false;
    private bool $forwardQuery = true;

    private function __construct()
    {
    }

    public static function createEmpty(): self
    {
        $instance = new self();
        $instance->longUrl = '';

        return $instance;
    }

    /**
     * @throws ValidationException
     */
    public static function fromRawData(array $data): self
    {
        $instance = new self();
        $instance->validateAndInit($data);

        return $instance;
    }

    /**
     * @throws ValidationException
     */
    private function validateAndInit(array $data): void
    {
        $inputFilter = ShortUrlInputFilter::withRequiredLongUrl($data);
        if (! $inputFilter->isValid()) {
            throw ValidationException::fromInputFilter($inputFilter);
        }

        $this->longUrl = $inputFilter->getValue(ShortUrlInputFilter::LONG_URL);
        $this->validSince = parseDateField($inputFilter->getValue(ShortUrlInputFilter::VALID_SINCE));
        $this->validUntil = parseDateField($inputFilter->getValue(ShortUrlInputFilter::VALID_UNTIL));
        $this->customSlug = $inputFilter->getValue(ShortUrlInputFilter::CUSTOM_SLUG);
        $this->maxVisits = getOptionalIntFromInputFilter($inputFilter, ShortUrlInputFilter::MAX_VISITS);
        $this->findIfExists = $inputFilter->getValue(ShortUrlInputFilter::FIND_IF_EXISTS);
        $this->validateUrl = getOptionalBoolFromInputFilter($inputFilter, ShortUrlInputFilter::VALIDATE_URL) ?? false;
        $this->domain = $inputFilter->getValue(ShortUrlInputFilter::DOMAIN);
        $this->shortCodeLength = getOptionalIntFromInputFilter(
            $inputFilter,
            ShortUrlInputFilter::SHORT_CODE_LENGTH,
        ) ?? DEFAULT_SHORT_CODES_LENGTH;
        $this->apiKey = $inputFilter->getValue(ShortUrlInputFilter::API_KEY);
        $this->tags = $inputFilter->getValue(ShortUrlInputFilter::TAGS);
        $this->title = $inputFilter->getValue(ShortUrlInputFilter::TITLE);
        $this->crawlable = $inputFilter->getValue(ShortUrlInputFilter::CRAWLABLE);
        $this->forwardQuery = getOptionalBoolFromInputFilter($inputFilter, ShortUrlInputFilter::FORWARD_QUERY) ?? true;
    }

    public function getLongUrl(): string
    {
        return $this->longUrl;
    }

    public function getValidSince(): ?Chronos
    {
        return $this->validSince;
    }

    public function hasValidSince(): bool
    {
        return $this->validSince !== null;
    }

    public function getValidUntil(): ?Chronos
    {
        return $this->validUntil;
    }

    public function hasValidUntil(): bool
    {
        return $this->validUntil !== null;
    }

    public function getCustomSlug(): ?string
    {
        return $this->customSlug;
    }

    public function hasCustomSlug(): bool
    {
        return $this->customSlug !== null;
    }

    public function getMaxVisits(): ?int
    {
        return $this->maxVisits;
    }

    public function hasMaxVisits(): bool
    {
        return $this->maxVisits !== null;
    }

    public function findIfExists(): bool
    {
        return (bool) $this->findIfExists;
    }

    public function hasDomain(): bool
    {
        return $this->domain !== null;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function getShortCodeLength(): int
    {
        return $this->shortCodeLength;
    }

    public function doValidateUrl(): bool
    {
        return $this->validateUrl;
    }

    public function getApiKey(): ?ApiKey
    {
        return $this->apiKey;
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function hasTitle(): bool
    {
        return $this->title !== null;
    }

    public function titleWasAutoResolved(): bool
    {
        return $this->titleWasAutoResolved;
    }

    public function withResolvedTitle(string $title): self
    {
        $copy = clone $this;
        $copy->title = $title;
        $copy->titleWasAutoResolved = true;

        return $copy;
    }

    public function isCrawlable(): bool
    {
        return $this->crawlable;
    }

    public function forwardQuery(): bool
    {
        return $this->forwardQuery;
    }
}
