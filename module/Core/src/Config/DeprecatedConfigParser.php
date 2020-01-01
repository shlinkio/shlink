<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config;

use function Functional\compose;

class DeprecatedConfigParser
{
    public function __invoke(array $config): array
    {
        return compose([$this, 'parseNotFoundRedirect'], [$this, 'removeSecretKey'])($config);
    }

    public function parseNotFoundRedirect(array $config): array
    {
        // If the new config value is already set, keep it
        if (isset($config['not_found_redirects']['invalid_short_url'])) {
            return $config;
        }

        $oldRedirectEnabled = $config['url_shortener']['not_found_short_url']['enable_redirection'] ?? false;
        if (! $oldRedirectEnabled) {
            return $config;
        }

        $oldRedirectValue = $config['url_shortener']['not_found_short_url']['redirect_to'] ?? null;
        $config['not_found_redirects']['invalid_short_url'] = $oldRedirectValue;

        return $config;
    }

    public function removeSecretKey(array $config): array
    {
        // Removing secret_key from any generated config will prevent the AppOptions object from crashing
        unset($config['app_options']['secret_key']);
        return $config;
    }
}
