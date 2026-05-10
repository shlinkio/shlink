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

    /**
     * @param string[]|null $tags
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
        #[TagsConverter]
        readonly public array|null $tags = null,
        readonly public bool $titleWasProvided = false,
        #[SubstringConverter(512)]
        readonly public string|null $title = null,
        readonly public bool $titleWasAutoResolved = false,
        readonly public bool|null $crawlable = null,
        readonly public bool|null $forwardQuery = null,
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
            tags: $this->tags,
            titleWasProvided: $this->titleWasProvided,
            title: $title,
            titleWasAutoResolved: true,
            crawlable: $this->crawlable,
            forwardQuery: $this->forwardQuery,
        );
    }

    public function hasTitle(): bool
    {
        return $this->titleWasProvided;
    }
}
