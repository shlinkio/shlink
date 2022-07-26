<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Mercure;

use Shlinkio\Shlink\Common\UpdatePublishing\Update;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;

interface MercureUpdatesGeneratorInterface
{
    public function newVisitUpdate(Visit $visit): Update;

    public function newOrphanVisitUpdate(Visit $visit): Update;

    public function newShortUrlVisitUpdate(Visit $visit): Update;

    public function newShortUrlUpdate(ShortUrl $shortUrl): Update;
}
