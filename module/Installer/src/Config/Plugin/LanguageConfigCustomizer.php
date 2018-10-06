<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Installer\Config\Plugin;

use Shlinkio\Shlink\Installer\Model\CustomizableAppConfig;
use Symfony\Component\Console\Style\SymfonyStyle;
use function array_diff;
use function array_keys;

class LanguageConfigCustomizer implements ConfigCustomizerInterface
{
    private const DEFAULT_LANG = 'DEFAULT';
    private const CLI_LANG = 'CLI';
    private const EXPECTED_KEYS = [
        self::DEFAULT_LANG,
        self::CLI_LANG,
    ];

    private const SUPPORTED_LANGUAGES = ['en', 'es'];

    public function process(SymfonyStyle $io, CustomizableAppConfig $appConfig): void
    {
        $lang = $appConfig->getLanguage();
        $keysToAskFor = $appConfig->hasLanguage()
            ? array_diff(self::EXPECTED_KEYS, array_keys($lang))
            : self::EXPECTED_KEYS;

        if (empty($keysToAskFor)) {
            return;
        }

        $io->title('LANGUAGE');
        foreach ($keysToAskFor as $key) {
            $lang[$key] = $this->ask($io, $key);
        }
        $appConfig->setLanguage($lang);
    }

    private function ask(SymfonyStyle $io, string $key)
    {
        switch ($key) {
            case self::DEFAULT_LANG:
                return $this->chooseLanguage($io, 'Select default language for the application in general');
            case self::CLI_LANG:
                return $this->chooseLanguage($io, 'Select default language for CLI executions');
        }

        return '';
    }

    private function chooseLanguage(SymfonyStyle $io, string $message): string
    {
        return $io->choice($message, self::SUPPORTED_LANGUAGES, self::SUPPORTED_LANGUAGES[0]);
    }
}
