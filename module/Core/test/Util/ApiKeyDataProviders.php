<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Util;

use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ApiKeyDataProviders
{
    public static function adminApiKeysProvider(): iterable
    {
        yield 'no API key' => [null];
        yield 'admin API key' => [ApiKey::create()];
    }
}
