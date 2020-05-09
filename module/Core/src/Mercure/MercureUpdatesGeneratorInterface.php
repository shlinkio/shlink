<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Mercure;

use Shlinkio\Shlink\Core\Entity\Visit;
use Symfony\Component\Mercure\Update;

interface MercureUpdatesGeneratorInterface
{
    public function newVisitUpdate(Visit $visit): Update;

    public function newShortUrlVisitUpdate(Visit $visit): Update;
}
