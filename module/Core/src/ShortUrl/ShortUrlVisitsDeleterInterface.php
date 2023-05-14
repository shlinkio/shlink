<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl;

use Shlinkio\Shlink\Core\Model\BulkDeleteResult;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface ShortUrlVisitsDeleterInterface
{
    public function deleteShortUrlVisits(ShortUrlIdentifier $identifier, ?ApiKey $apiKey): BulkDeleteResult;
}
