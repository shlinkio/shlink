<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Installer\Config\Plugin;

use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Installer\Model\CustomizableAppConfig;
use Shlinkio\Shlink\Installer\Util\AskUtilsTrait;
use Symfony\Component\Console\Style\SymfonyStyle;
use function array_diff;
use function array_keys;
use function str_shuffle;

class UrlShortenerConfigCustomizer implements ConfigCustomizerInterface
{
    use AskUtilsTrait;

    public const SCHEMA = 'SCHEMA';
    public const HOSTNAME = 'HOSTNAME';
    public const CHARS = 'CHARS';
    public const VALIDATE_URL = 'VALIDATE_URL';
    public const ENABLE_NOT_FOUND_REDIRECTION = 'ENABLE_NOT_FOUND_REDIRECTION';
    public const NOT_FOUND_REDIRECT_TO = 'NOT_FOUND_REDIRECT_TO';
    private const EXPECTED_KEYS = [
        self::SCHEMA,
        self::HOSTNAME,
        self::CHARS,
        self::VALIDATE_URL,
        self::ENABLE_NOT_FOUND_REDIRECTION,
        self::NOT_FOUND_REDIRECT_TO,
    ];

    public function process(SymfonyStyle $io, CustomizableAppConfig $appConfig): void
    {
        $urlShortener = $appConfig->getUrlShortener();
        $doImport = $appConfig->hasUrlShortener();
        $keysToAskFor = $doImport ? array_diff(self::EXPECTED_KEYS, array_keys($urlShortener)) : self::EXPECTED_KEYS;

        if (empty($keysToAskFor)) {
            return;
        }

        $io->title('URL SHORTENER');
        foreach ($keysToAskFor as $key) {
            // Skip not found redirect URL when the user decided not to redirect
            if ($key === self::NOT_FOUND_REDIRECT_TO && ! $urlShortener[self::ENABLE_NOT_FOUND_REDIRECTION]) {
                continue;
            }

            $urlShortener[$key] = $this->ask($io, $key);
        }
        $appConfig->setUrlShortener($urlShortener);
    }

    private function ask(SymfonyStyle $io, string $key)
    {
        switch ($key) {
            case self::SCHEMA:
                return $io->choice(
                    'Select schema for generated short URLs',
                    ['http', 'https'],
                    'http'
                );
            case self::HOSTNAME:
                return $this->askRequired($io, 'hostname', 'Hostname for generated URLs');
            case self::CHARS:
                return $io->ask(
                    'Character set for generated short codes (leave empty to autogenerate one)'
                ) ?: str_shuffle(UrlShortenerOptions::DEFAULT_CHARS);
            case self::VALIDATE_URL:
                return $io->confirm('Do you want to validate long urls by 200 HTTP status code on response');
            case self::ENABLE_NOT_FOUND_REDIRECTION:
                return $io->confirm(
                    'Do you want to enable a redirection to a custom URL when a user hits an invalid short URL? ' .
                    '(If not enabled, the user will see a default "404 not found" page)',
                    false
                );
            case self::NOT_FOUND_REDIRECT_TO:
                return $this->askRequired(
                    $io,
                    'redirect URL',
                    'Custom URL to redirect to when a user hits an invalid short URL'
                );
        }

        return '';
    }
}
