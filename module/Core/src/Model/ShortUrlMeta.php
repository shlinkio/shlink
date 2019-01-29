<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Cake\Chronos\Chronos;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Validation\ShortUrlMetaInputFilter;
use function is_string;

final class ShortUrlMeta
{
    /** @var Chronos|null */
    private $validSince;
    /** @var Chronos|null */
    private $validUntil;
    /** @var string|null */
    private $customSlug;
    /** @var int|null */
    private $maxVisits;

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
     * @throws ValidationException
     */
    public static function createFromParams(
        $validSince = null,
        $validUntil = null,
        $customSlug = null,
        $maxVisits = null
    ): self {
        // We do not type hint the arguments because that will be done by the validation process
        $instance = new self();
        $instance->validate([
            ShortUrlMetaInputFilter::VALID_SINCE => $validSince,
            ShortUrlMetaInputFilter::VALID_UNTIL => $validUntil,
            ShortUrlMetaInputFilter::CUSTOM_SLUG => $customSlug,
            ShortUrlMetaInputFilter::MAX_VISITS => $maxVisits,
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
        $this->maxVisits = $inputFilter->getValue(ShortUrlMetaInputFilter::MAX_VISITS);
        $this->maxVisits = $this->maxVisits !== null ? (int) $this->maxVisits : null;
    }

    /**
     * @param string|Chronos|null $date
     * @return Chronos|null
     */
    private function parseDateField($date): ?Chronos
    {
        if ($date === null || $date instanceof Chronos) {
            return $date;
        }

        if (is_string($date)) {
            return Chronos::parse($date);
        }

        return null;
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

    public function withCustomSlug(string $customSlug): self
    {
        $clone = clone $this;
        $clone->customSlug = $customSlug;

        return $clone;
    }
}
