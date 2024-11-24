<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl;

use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\BulkDeleteResult;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface ShortUrlVisitsDeleterInterface
{
    /**
     * @throws ShortUrlNotFoundException
     */
    public function deleteShortUrlVisits(ShortUrlIdentifier $identifier, ApiKey|null $apiKey = null): BulkDeleteResult;
}
