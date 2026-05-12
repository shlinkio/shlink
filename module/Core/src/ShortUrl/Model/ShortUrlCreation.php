<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use Cake\Chronos\Chronos;
use DateTimeInterface;
use Shlinkio\Shlink\Common\ObjectMapper\HostAndPortConverter;
use Shlinkio\Shlink\Common\ObjectMapper\LooseUriConverter;
use Shlinkio\Shlink\Common\ObjectMapper\MappingError;
use Shlinkio\Shlink\Common\ObjectMapper\SubstringConverter;
use Shlinkio\Shlink\Common\ObjectMapper\TagsConverter;
use Shlinkio\Shlink\Core\ShortUrl\Helper\TitleResolutionModelInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function Shlinkio\Shlink\Common\normalizeOptionalDate;
use function str_replace;
use function strpbrk;
use function strtolower;
use function trim;

use const Shlinkio\Shlink\DEFAULT_SHORT_CODES_LENGTH;

final readonly class ShortUrlCreation implements TitleResolutionModelInterface
{
    public Chronos|null $validSince;
    public Chronos|null $validUntil;
    public string|null $customSlug;
    public string|null $pathPrefix;

    /**
     * @param string[] $tags
     * @param int<4, max> $shortCodeLength
     */
    public function __construct(
        #[LooseUriConverter]
        public string $longUrl,
        DateTimeInterface|string|null $validSince = null,
        DateTimeInterface|string|null $validUntil = null,
        public ShortUrlMode $shortUrlMode = ShortUrlMode::STRICT,
        private bool $multiSegmentSlugsEnabled = false,
        string|null $customSlug = null,
        string|null $pathPrefix = null,
        public int|null $maxVisits = null,
        public bool $findIfExists = false,
        #[HostAndPortConverter]
        public string|null $domain = null,
        public int $shortCodeLength = DEFAULT_SHORT_CODES_LENGTH,
        public ApiKey|null $apiKey = null,
        #[TagsConverter]
        public array $tags = [],
        #[SubstringConverter(512)]
        public string|null $title = null,
        public bool $titleWasAutoResolved = false,
        public bool $crawlable = false,
        public bool $forwardQuery = true,
    ) {
        $this->validSince = normalizeOptionalDate($validSince);
        $this->validUntil = normalizeOptionalDate($validUntil);

        $this->customSlug = $this->filterAndValidateOptionsRelatedValue($customSlug);
        $this->pathPrefix = $this->filterAndValidateOptionsRelatedValue($pathPrefix);
    }

    private function filterAndValidateOptionsRelatedValue(string|null $value): string|null
    {
        if ($value === null) {
            return null;
        }

        $isLooseMode = $this->shortUrlMode === ShortUrlMode::LOOSE;
        $value = $isLooseMode ? strtolower($value) : $value;
        $value = $this->multiSegmentSlugsEnabled
            ? trim(str_replace(' ', '-', $value), '/')
            : str_replace([' ', '/'], '-', $value);

        // URL gen-delimiter reserved characters, except `/`: https://datatracker.ietf.org/doc/html/rfc3986#section-2.2
        $reservedChars = ':?#[]@';
        if (! $this->multiSegmentSlugsEnabled) {
            // Slashes should only be allowed if multi-segment slugs are enabled
            $reservedChars .= '/';
        }

        if (strpbrk($value, $reservedChars) !== false) {
            throw MappingError::withBody('URL-reserved characters cannot be used in a custom slug or path prefix');
        }

        return $value;
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
            shortUrlMode: $this->shortUrlMode,
            multiSegmentSlugsEnabled: $this->multiSegmentSlugsEnabled,
            customSlug: $this->customSlug,
            pathPrefix: $this->pathPrefix,
            maxVisits: $this->maxVisits,
            findIfExists: $this->findIfExists,
            domain: $this->domain,
            shortCodeLength: $this->shortCodeLength,
            apiKey: $this->apiKey,
            tags: $this->tags,
            title: $title,
            titleWasAutoResolved: true,
            crawlable: $this->crawlable,
            forwardQuery: $this->forwardQuery,
        );
    }

    public function hasValidSince(): bool
    {
        return $this->validSince !== null;
    }

    public function hasValidUntil(): bool
    {
        return $this->validUntil !== null;
    }

    public function hasCustomSlug(): bool
    {
        return $this->customSlug !== null;
    }

    public function hasMaxVisits(): bool
    {
        return $this->maxVisits !== null;
    }

    public function hasDomain(): bool
    {
        return $this->domain !== null;
    }

    public function hasTitle(): bool
    {
        return $this->title !== null;
    }
}
