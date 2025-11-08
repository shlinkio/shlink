<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config;

final class EmptyNotFoundRedirectConfig implements NotFoundRedirectConfigInterface
{
    private(set) string|null $invalidShortUrlRedirect = null;
    private(set) string|null $regular404Redirect = null;
    private(set) string|null $baseUrlRedirect = null;
}
