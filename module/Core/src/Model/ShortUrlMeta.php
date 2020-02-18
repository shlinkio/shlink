<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Cake\Chronos\Chronos;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Validation\ShortUrlMetaInputFilter;

use function array_key_exists;
use function Shlinkio\Shlink\Core\parseDateField;

use const Shlinkio\Shlink\Core\DEFAULT_SHORT_CODES_LENGTH;

final class ShortUrlMeta
{
    private bool $validSincePropWasProvided = false;
    private ?Chronos $validSince = null;
    private bool $validUntilPropWasProvided = false;
    private ?Chronos $validUntil = null;
    private ?string $customSlug = null;
    private bool $maxVisitsPropWasProvided = false;
    private ?int $maxVisits = null;
    private ?bool $findIfExists = null;
    private ?string $domain = null;
    private int $shortCodeLength = 5;

    // Force named constructors
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
        $this->validSincePropWasProvided = array_key_exists(ShortUrlMetaInputFilter::VALID_SINCE, $data);
        $this->validUntil = parseDateField($inputFilter->getValue(ShortUrlMetaInputFilter::VALID_UNTIL));
        $this->validUntilPropWasProvided = array_key_exists(ShortUrlMetaInputFilter::VALID_UNTIL, $data);
        $this->customSlug = $inputFilter->getValue(ShortUrlMetaInputFilter::CUSTOM_SLUG);
        $this->maxVisits = $this->getOptionalIntFromInputFilter($inputFilter, ShortUrlMetaInputFilter::MAX_VISITS);
        $this->maxVisitsPropWasProvided = array_key_exists(ShortUrlMetaInputFilter::MAX_VISITS, $data);
        $this->findIfExists = $inputFilter->getValue(ShortUrlMetaInputFilter::FIND_IF_EXISTS);
        $this->domain = $inputFilter->getValue(ShortUrlMetaInputFilter::DOMAIN);
        $this->shortCodeLength = $this->getOptionalIntFromInputFilter(
            $inputFilter,
            ShortUrlMetaInputFilter::SHORT_CODE_LENGTH,
        ) ?? DEFAULT_SHORT_CODES_LENGTH;
    }

    private function getOptionalIntFromInputFilter(ShortUrlMetaInputFilter $inputFilter, string $fieldName): ?int
    {
        $value = $inputFilter->getValue($fieldName);
        return $value !== null ? (int) $value : null;
    }

    public function getValidSince(): ?Chronos
    {
        return $this->validSince;
    }

    public function hasValidSince(): bool
    {
        return $this->validSincePropWasProvided;
    }

    public function getValidUntil(): ?Chronos
    {
        return $this->validUntil;
    }

    public function hasValidUntil(): bool
    {
        return $this->validUntilPropWasProvided;
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
        return $this->maxVisitsPropWasProvided;
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
}
