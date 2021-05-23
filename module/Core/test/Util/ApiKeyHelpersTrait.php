<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Util;

use Shlinkio\Shlink\Rest\Entity\ApiKey;

trait ApiKeyHelpersTrait
{
    public function provideAdminApiKeys(): iterable
    {
        yield 'no API key' => [null];
        yield 'admin API key' => [ApiKey::create()];
    }
}
