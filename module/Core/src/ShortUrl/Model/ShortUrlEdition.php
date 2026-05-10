<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use Cake\Chronos\Chronos;
use DateTimeInterface;
use Shlinkio\Shlink\Common\ObjectMapper\LooseUriConverter;
use Shlinkio\Shlink\Common\ObjectMapper\SubstringConverter;
use Shlinkio\Shlink\Common\ObjectMapper\TagsConverter;
use Shlinkio\Shlink\Core\ShortUrl\Helper\TitleResolutionModelInterface;

use function Shlinkio\Shlink\Common\normalizeOptionalDate;

final class ShortUrlEdition implements TitleResolutionModelInterface
{
    public Chronos|null $validSince;
    public Chronos|null $validUntil;

    // phpcs:disable PSR2.Classes.PropertyDeclaration.Multiple
    public bool $longUrlWasProvided {
        // phpcs:disable PSR2.Classes.PropertyDeclaration.ScopeMissing
        get => $this->longUrl !== null;
    }

    /**
     * @param string[] $tags
     */
    public function __construct(
        #[LooseUriConverter]
        readonly public string|null $longUrl = null,
        readonly public bool $validSinceWasProvided = false,
        DateTimeInterface|string|null $validSince = null,
        readonly public bool $validUntilWasProvided = false,
        DateTimeInterface|string|null $validUntil = null,
        readonly public bool $maxVisitsWasProvided = false,
        readonly public int|null $maxVisits = null,
        readonly public bool $tagsWereProvided = false,
        #[TagsConverter]
        readonly public array $tags = [],
        readonly public bool $titleWasProvided = false,
        #[SubstringConverter(512)]
        readonly public string|null $title = null,
        readonly public bool $titleWasAutoResolved = false,
        readonly public bool $crawlableWasProvided = false,
        readonly public bool $crawlable = false,
        readonly public bool $forwardQueryWasProvided = false,
        readonly public bool $forwardQuery = true,
    ) {
        $this->validSince = normalizeOptionalDate($validSince);
        $this->validUntil = normalizeOptionalDate($validUntil);
    }

    public function withResolvedTitle(string $title): static
    {
        // TODO Use clone with once PHP 8.4 is no longer supported
        // return clone($this, [
        //     'title' => $title,
        //     'titleWasAutoResolved' => true,
        // ]);

        return new self(
            longUrl: $this->longUrl,
            validSinceWasProvided: $this->validSinceWasProvided,
            validSince: $this->validSince,
            validUntilWasProvided: $this->validUntilWasProvided,
            validUntil: $this->validUntil,
            maxVisitsWasProvided: $this->maxVisitsWasProvided,
            maxVisits: $this->maxVisits,
            tagsWereProvided: $this->tagsWereProvided,
            tags: $this->tags,
            titleWasProvided: $this->titleWasProvided,
            title: $title,
            titleWasAutoResolved: true,
            crawlableWasProvided: $this->crawlableWasProvided,
            crawlable: $this->crawlable,
            forwardQueryWasProvided: $this->forwardQueryWasProvided,
            forwardQuery: $this->forwardQuery,
        );
    }

    public function hasTitle(): bool
    {
        return $this->titleWasProvided;
    }
}
