<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config;

interface NotFoundRedirectConfigInterface
{
    public string|null $invalidShortUrlRedirect { get; }
    public string|null $regular404Redirect { get; }
    public string|null $baseUrlRedirect { get; }
    public string|null $expiredShortUrlRedirect { get; }
}
