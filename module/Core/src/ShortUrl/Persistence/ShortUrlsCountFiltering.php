<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Persistence;

use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\ShortUrl\Model\TagsMode;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function str_contains;
use function strtolower;

class ShortUrlsCountFiltering
{
    public readonly bool $searchIncludesDefaultDomain;
    public readonly string|null $apiKeyName;

    /**
     * @param $defaultDomain - Used only to determine if search term includes default domain
     */
    public function __construct(
        public readonly string|null $searchTerm = null,
        public readonly array $tags = [],
        public readonly TagsMode $tagsMode = TagsMode::ANY,
        public readonly DateRange|null $dateRange = null,
        public readonly bool $excludeMaxVisitsReached = false,
        public readonly bool $excludePastValidUntil = false,
        public readonly ApiKey|null $apiKey = null,
        string|null $defaultDomain = null,
        public readonly string|null $domain = null,
        public readonly array $excludeTags = [],
        public readonly TagsMode $excludeTagsMode = TagsMode::ANY,
        string|null $apiKeyName = null,
    ) {
        $this->searchIncludesDefaultDomain = !empty($searchTerm) && !empty($defaultDomain) && str_contains(
            strtolower($defaultDomain),
            strtolower($searchTerm),
        );

        // Filtering by API key name is only allowed if the API key used in the request is an admin one, or it matches
        // the API key name
        $this->apiKeyName = $apiKey?->name === $apiKeyName || ApiKey::isAdmin($apiKey) ? $apiKeyName : null;
    }

    public static function fromParams(ShortUrlsParams $params, ApiKey|null $apiKey, string $defaultDomain): self
    {
        return new self(
            $params->searchTerm,
            $params->tags,
            $params->tagsMode,
            $params->dateRange,
            $params->excludeMaxVisitsReached,
            $params->excludePastValidUntil,
            $apiKey,
            $defaultDomain,
            $params->domain,
            $params->excludeTags,
            $params->excludeTagsMode,
            $params->apiKeyName,
        );
    }
}
