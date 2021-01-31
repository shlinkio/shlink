<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Cake\Chronos\Chronos;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Validation\ShortUrlInputFilter;

use function array_key_exists;
use function Shlinkio\Shlink\Core\getOptionalBoolFromInputFilter;
use function Shlinkio\Shlink\Core\getOptionalIntFromInputFilter;
use function Shlinkio\Shlink\Core\parseDateField;

final class ShortUrlEdit
{
    private bool $longUrlPropWasProvided = false;
    private ?string $longUrl = null;
    private bool $validSincePropWasProvided = false;
    private ?Chronos $validSince = null;
    private bool $validUntilPropWasProvided = false;
    private ?Chronos $validUntil = null;
    private bool $maxVisitsPropWasProvided = false;
    private ?int $maxVisits = null;
    private bool $tagsPropWasProvided = false;
    private array $tags = [];
    private ?bool $validateUrl = null;

    private function __construct()
    {
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
        $inputFilter = ShortUrlInputFilter::withNonRequiredLongUrl($data);
        if (! $inputFilter->isValid()) {
            throw ValidationException::fromInputFilter($inputFilter);
        }

        $this->longUrlPropWasProvided = array_key_exists(ShortUrlInputFilter::LONG_URL, $data);
        $this->validSincePropWasProvided = array_key_exists(ShortUrlInputFilter::VALID_SINCE, $data);
        $this->validUntilPropWasProvided = array_key_exists(ShortUrlInputFilter::VALID_UNTIL, $data);
        $this->maxVisitsPropWasProvided = array_key_exists(ShortUrlInputFilter::MAX_VISITS, $data);
        $this->tagsPropWasProvided = array_key_exists(ShortUrlInputFilter::TAGS, $data);

        $this->longUrl = $inputFilter->getValue(ShortUrlInputFilter::LONG_URL);
        $this->validSince = parseDateField($inputFilter->getValue(ShortUrlInputFilter::VALID_SINCE));
        $this->validUntil = parseDateField($inputFilter->getValue(ShortUrlInputFilter::VALID_UNTIL));
        $this->maxVisits = getOptionalIntFromInputFilter($inputFilter, ShortUrlInputFilter::MAX_VISITS);
        $this->validateUrl = getOptionalBoolFromInputFilter($inputFilter, ShortUrlInputFilter::VALIDATE_URL);
        $this->tags = $inputFilter->getValue(ShortUrlInputFilter::TAGS);
    }

    public function longUrl(): ?string
    {
        return $this->longUrl;
    }

    public function hasLongUrl(): bool
    {
        return $this->longUrlPropWasProvided && $this->longUrl !== null;
    }

    public function validSince(): ?Chronos
    {
        return $this->validSince;
    }

    public function hasValidSince(): bool
    {
        return $this->validSincePropWasProvided;
    }

    public function validUntil(): ?Chronos
    {
        return $this->validUntil;
    }

    public function hasValidUntil(): bool
    {
        return $this->validUntilPropWasProvided;
    }

    public function maxVisits(): ?int
    {
        return $this->maxVisits;
    }

    public function hasMaxVisits(): bool
    {
        return $this->maxVisitsPropWasProvided;
    }

    /**
     * @return string[]
     */
    public function tags(): array
    {
        return $this->tags;
    }

    public function hasTags(): bool
    {
        return $this->tagsPropWasProvided;
    }

    public function doValidateUrl(): ?bool
    {
        return $this->validateUrl;
    }
}
