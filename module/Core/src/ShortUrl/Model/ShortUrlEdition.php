<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use Cake\Chronos\Chronos;
use DateTimeInterface;
use Shlinkio\Shlink\Common\ObjectMapper\LooseUriConverter;
use Shlinkio\Shlink\Common\ObjectMapper\SubstringConverter;
use Shlinkio\Shlink\Common\ObjectMapper\TagsConverter;
use Shlinkio\Shlink\Core\ShortUrl\Helper\TitleResolutionModelInterface;
use Shlinkio\Shlink\Core\Util\NoValue;

use function Shlinkio\Shlink\Common\normalizeOptionalDate;

final readonly class ShortUrlEdition implements TitleResolutionModelInterface
{
    public Chronos|null $validSince;
    public bool $validSinceWasProvided;
    public Chronos|null $validUntil;
    public bool $validUntilWasProvided;
    public int|null $maxVisits;
    public bool $maxVisitsWasProvided;
    public string|null $title;
    public bool $titleWasProvided;

    /**
     * @param positive-int|NoValue|null $maxVisits
     * @param string[]|null $tags
     */
    public function __construct(
        #[LooseUriConverter]
        public string|null $longUrl = null,
        DateTimeInterface|string|NoValue|null $validSince = NoValue::NO_VALUE,
        DateTimeInterface|string|NoValue|null $validUntil = NoValue::NO_VALUE,
        int|NoValue|null $maxVisits = NoValue::NO_VALUE,
        #[TagsConverter]
        public array|null $tags = null,
        #[SubstringConverter(512)]
        string|NoValue|null $title = NoValue::NO_VALUE,
        public bool $titleWasAutoResolved = false,
        public bool|null $crawlable = null,
        public bool|null $forwardQuery = null,
    ) {
        $this->validSince = normalizeOptionalDate(NoValue::resolve($validSince));
        $this->validSinceWasProvided = $validSince !== NoValue::NO_VALUE;

        $this->validUntil = normalizeOptionalDate(NoValue::resolve($validUntil));
        $this->validUntilWasProvided = $validUntil !== NoValue::NO_VALUE;

        $this->maxVisits = NoValue::resolve($maxVisits);
        $this->maxVisitsWasProvided = $maxVisits !== NoValue::NO_VALUE;

        $this->title = NoValue::resolve($title);
        $this->titleWasProvided = $title !== NoValue::NO_VALUE;
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
            validSince: $this->validSince,
            validUntil: $this->validUntil,
            maxVisits: $this->maxVisits,
            tags: $this->tags,
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
