<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Cake\Chronos\Chronos;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Validation\ShortUrlMetaInputFilter;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function Shlinkio\Shlink\Core\getOptionalBoolFromInputFilter;
use function Shlinkio\Shlink\Core\getOptionalIntFromInputFilter;
use function Shlinkio\Shlink\Core\parseDateField;

use const Shlinkio\Shlink\Core\DEFAULT_SHORT_CODES_LENGTH;

final class ShortUrlMeta
{
    private ?Chronos $validSince = null;
    private ?Chronos $validUntil = null;
    private ?string $customSlug = null;
    private ?int $maxVisits = null;
    private ?bool $findIfExists = null;
    private ?string $domain = null;
    private int $shortCodeLength = 5;
    private ?bool $validateUrl = null;
    private ?ApiKey $apiKey = null;

    // Enforce named constructors
    private function __construct()
    {
    }

    public static function createEmpty(): self
    {
        return new self();
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
        $inputFilter = new ShortUrlMetaInputFilter($data);
        if (! $inputFilter->isValid()) {
            throw ValidationException::fromInputFilter($inputFilter);
        }

        $this->validSince = parseDateField($inputFilter->getValue(ShortUrlMetaInputFilter::VALID_SINCE));
        $this->validUntil = parseDateField($inputFilter->getValue(ShortUrlMetaInputFilter::VALID_UNTIL));
        $this->customSlug = $inputFilter->getValue(ShortUrlMetaInputFilter::CUSTOM_SLUG);
        $this->maxVisits = getOptionalIntFromInputFilter($inputFilter, ShortUrlMetaInputFilter::MAX_VISITS);
        $this->findIfExists = $inputFilter->getValue(ShortUrlMetaInputFilter::FIND_IF_EXISTS);
        $this->validateUrl = getOptionalBoolFromInputFilter($inputFilter, ShortUrlMetaInputFilter::VALIDATE_URL);
        $this->domain = $inputFilter->getValue(ShortUrlMetaInputFilter::DOMAIN);
        $this->shortCodeLength = getOptionalIntFromInputFilter(
            $inputFilter,
            ShortUrlMetaInputFilter::SHORT_CODE_LENGTH,
        ) ?? DEFAULT_SHORT_CODES_LENGTH;
        $this->apiKey = $inputFilter->getValue(ShortUrlMetaInputFilter::API_KEY);
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

    public function doValidateUrl(): ?bool
    {
        return $this->validateUrl;
    }

    public function getApiKey(): ?ApiKey
    {
        return $this->apiKey;
    }
}
