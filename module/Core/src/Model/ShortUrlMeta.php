<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Cake\Chronos\Chronos;
use DateTimeInterface;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Validation\ShortUrlMetaInputFilter;

final class ShortUrlMeta
{
    private ?Chronos $validSince = null;
    private ?Chronos $validUntil = null;
    private ?string $customSlug = null;
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
    public static function createFromRawData(array $data): self
    {
        $instance = new self();
        $instance->validate($data);
        return $instance;
    }

    /**
     * @param string|Chronos|null $validSince
     * @param string|Chronos|null $validUntil
     * @param string|null $customSlug
     * @param int|null $maxVisits
     * @param bool|null $findIfExists
     * @param string|null $domain
     * @throws ValidationException
     */
    public static function createFromParams(
        $validSince = null,
        $validUntil = null,
        $customSlug = null,
        $maxVisits = null,
        $findIfExists = null,
        $domain = null
    ): self {
        // We do not type hint the arguments because that will be done by the validation process and we would get a
        // type error if any of them do not match
        $instance = new self();
        $instance->validate([
            ShortUrlMetaInputFilter::VALID_SINCE => $validSince,
            ShortUrlMetaInputFilter::VALID_UNTIL => $validUntil,
            ShortUrlMetaInputFilter::CUSTOM_SLUG => $customSlug,
            ShortUrlMetaInputFilter::MAX_VISITS => $maxVisits,
            ShortUrlMetaInputFilter::FIND_IF_EXISTS => $findIfExists,
            ShortUrlMetaInputFilter::DOMAIN => $domain,
        ]);
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
