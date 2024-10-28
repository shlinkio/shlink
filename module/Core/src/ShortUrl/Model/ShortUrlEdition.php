<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use Cake\Chronos\Chronos;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\ShortUrl\Helper\TitleResolutionModelInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlInputFilter;

use function array_key_exists;
use function Shlinkio\Shlink\Core\getOptionalBoolFromInputFilter;
use function Shlinkio\Shlink\Core\getOptionalIntFromInputFilter;
use function Shlinkio\Shlink\Core\normalizeOptionalDate;

final readonly class ShortUrlEdition implements TitleResolutionModelInterface
{
    /**
     * @param string[] $tags
     */
    private function __construct(
        private bool $longUrlPropWasProvided = false,
        public string|null $longUrl = null,
        private bool $validSincePropWasProvided = false,
        public Chronos|null $validSince = null,
        private bool $validUntilPropWasProvided = false,
        public Chronos|null $validUntil = null,
        private bool $maxVisitsPropWasProvided = false,
        public int|null $maxVisits = null,
        private bool $tagsPropWasProvided = false,
        public array $tags = [],
        private bool $titlePropWasProvided = false,
        public string|null $title = null,
        public bool $titleWasAutoResolved = false,
        private bool $crawlablePropWasProvided = false,
        public bool $crawlable = false,
        private bool $forwardQueryPropWasProvided = false,
        public bool $forwardQuery = true,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public static function fromRawData(array $data): self
    {
        $inputFilter = ShortUrlInputFilter::forEdition($data);
        if (! $inputFilter->isValid()) {
            throw ValidationException::fromInputFilter($inputFilter);
        }

        return new self(
            longUrlPropWasProvided: array_key_exists(ShortUrlInputFilter::LONG_URL, $data),
            longUrl: $inputFilter->getValue(ShortUrlInputFilter::LONG_URL),
            validSincePropWasProvided: array_key_exists(ShortUrlInputFilter::VALID_SINCE, $data),
            validSince: normalizeOptionalDate($inputFilter->getValue(ShortUrlInputFilter::VALID_SINCE)),
            validUntilPropWasProvided: array_key_exists(ShortUrlInputFilter::VALID_UNTIL, $data),
            validUntil: normalizeOptionalDate($inputFilter->getValue(ShortUrlInputFilter::VALID_UNTIL)),
            maxVisitsPropWasProvided: array_key_exists(ShortUrlInputFilter::MAX_VISITS, $data),
            maxVisits: getOptionalIntFromInputFilter($inputFilter, ShortUrlInputFilter::MAX_VISITS),
            tagsPropWasProvided: array_key_exists(ShortUrlInputFilter::TAGS, $data),
            tags: $inputFilter->getValue(ShortUrlInputFilter::TAGS) ?? [],
            titlePropWasProvided: array_key_exists(ShortUrlInputFilter::TITLE, $data),
            title: $inputFilter->getValue(ShortUrlInputFilter::TITLE),
            crawlablePropWasProvided: array_key_exists(ShortUrlInputFilter::CRAWLABLE, $data),
            crawlable: $inputFilter->getValue(ShortUrlInputFilter::CRAWLABLE),
            forwardQueryPropWasProvided: array_key_exists(ShortUrlInputFilter::FORWARD_QUERY, $data),
            forwardQuery: getOptionalBoolFromInputFilter($inputFilter, ShortUrlInputFilter::FORWARD_QUERY) ?? true,
        );
    }

    public function withResolvedTitle(string $title): static
    {
        return new self(
            longUrlPropWasProvided: $this->longUrlPropWasProvided,
            longUrl: $this->longUrl,
            validSincePropWasProvided: $this->validSincePropWasProvided,
            validSince: $this->validSince,
            validUntilPropWasProvided: $this->validUntilPropWasProvided,
            validUntil: $this->validUntil,
            maxVisitsPropWasProvided: $this->maxVisitsPropWasProvided,
            maxVisits: $this->maxVisits,
            tagsPropWasProvided: $this->tagsPropWasProvided,
            tags: $this->tags,
            titlePropWasProvided: $this->titlePropWasProvided,
            title: $title,
            titleWasAutoResolved: true,
            crawlablePropWasProvided: $this->crawlablePropWasProvided,
            crawlable: $this->crawlable,
            forwardQueryPropWasProvided: $this->forwardQueryPropWasProvided,
            forwardQuery: $this->forwardQuery,
        );
    }

    public function getLongUrl(): string
    {
        return $this->longUrl ?? '';
    }

    public function longUrlWasProvided(): bool
    {
        return $this->longUrlPropWasProvided && $this->longUrl !== null;
    }

    public function validSinceWasProvided(): bool
    {
        return $this->validSincePropWasProvided;
    }

    public function validUntilWasProvided(): bool
    {
        return $this->validUntilPropWasProvided;
    }

    public function maxVisitsWasProvided(): bool
    {
        return $this->maxVisitsPropWasProvided;
    }

    public function tagsWereProvided(): bool
    {
        return $this->tagsPropWasProvided;
    }

    public function titleWasProvided(): bool
    {
        return $this->titlePropWasProvided;
    }

    public function hasTitle(): bool
    {
        return $this->titleWasProvided();
    }

    public function titleWasAutoResolved(): bool
    {
        return $this->titleWasAutoResolved;
    }

    public function crawlableWasProvided(): bool
    {
        return $this->crawlablePropWasProvided;
    }

    public function forwardQueryWasProvided(): bool
    {
        return $this->forwardQueryPropWasProvided;
    }
}
