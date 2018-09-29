<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Installer\Config\Plugin;

use Shlinkio\Shlink\Core\Service\UrlShortener;
use Shlinkio\Shlink\Installer\Model\CustomizableAppConfig;
use Symfony\Component\Console\Style\SymfonyStyle;
use function str_shuffle;

class UrlShortenerConfigCustomizer implements ConfigCustomizerInterface
{
    public function process(SymfonyStyle $io, CustomizableAppConfig $appConfig): void
    {
        $io->title('URL SHORTENER');

        if ($appConfig->hasUrlShortener() && $io->confirm('Do you want to keep imported URL shortener config?')) {
            return;
        }

        // Ask for URL shortener params
        $appConfig->setUrlShortener([
            'SCHEMA' => $io->choice(
                'Select schema for generated short URLs',
                ['http', 'https'],
                'http'
            ),
            'HOSTNAME' => $io->ask('Hostname for generated URLs'),
            'CHARS' => $io->ask(
                'Character set for generated short codes (leave empty to autogenerate one)',
                null,
                function ($value) {
                    return $value;
                }
            ) ?: str_shuffle(UrlShortener::DEFAULT_CHARS),
            'VALIDATE_URL' => $io->confirm('Do you want to validate long urls by 200 HTTP status code on response'),
        ]);
    }
}
