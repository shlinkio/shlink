<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Cake\Chronos\Chronos;
use DateTimeInterface;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Validation\ShortUrlMetaInputFilter;

use function array_key_exists;

final class ShortUrlMeta
{
    private bool $hasValidSince = false;
    private ?Chronos $validSince = null;
    private bool $hasValidUntil = false;
    private ?Chronos $validUntil = null;
    private ?string $customSlug = null;
    private bool $hasMaxVisits = false;
    private ?int $maxVisits = null;
    private ?bool $findIfExists = null;
    private ?string $domain = null;

    // Force named constructors
    private function __construct()
    {
    }

    public static function createEmpty(): self
    {
        return new self();
    }

    /**
     * @param array $data
     * @throws ValidationException
     */
    public static function fromRawData(array $data): self
    {
        $instance = new self();
        $instance->validate($data);
        return $instance;
    }

    /**
     * @param array $data
     * @throws ValidationException
     */
    private function validate(array $data): void
    {
        $inputFilter = new ShortUrlMetaInputFilter($data);
        if (! $inputFilter->isValid()) {
            throw ValidationException::fromInputFilter($inputFilter);
        }

        $this->validSince = $this->parseDateField($inputFilter->getValue(ShortUrlMetaInputFilter::VALID_SINCE));
        $this->hasValidSince = array_key_exists(ShortUrlMetaInputFilter::VALID_SINCE, $data);
        $this->validUntil = $this->parseDateField($inputFilter->getValue(ShortUrlMetaInputFilter::VALID_UNTIL));
        $this->customSlug = $inputFilter->getValue(ShortUrlMetaInputFilter::CUSTOM_SLUG);
        $maxVisits = $inputFilter->getValue(ShortUrlMetaInputFilter::MAX_VISITS);
        $this->maxVisits = $maxVisits !== null ? (int) $maxVisits : null;
        $this->findIfExists = $inputFilter->getValue(ShortUrlMetaInputFilter::FIND_IF_EXISTS);
        $this->domain = $inputFilter->getValue(ShortUrlMetaInputFilter::DOMAIN);
    }

    /**
     * @param string|DateTimeInterface|Chronos|null $date
     */
    private function parseDateField($date): ?Chronos
    {
        if ($date === null || $date instanceof Chronos) {
            return $date;
        }

        if ($date instanceof DateTimeInterface) {
            return Chronos::instance($date);
        }

        return Chronos::parse($date);
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
}
