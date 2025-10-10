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
    ) {
        $this->searchIncludesDefaultDomain = !empty($searchTerm) && !empty($defaultDomain) && str_contains(
            strtolower($defaultDomain),
            strtolower($searchTerm),
        );
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
        );
    }
}
