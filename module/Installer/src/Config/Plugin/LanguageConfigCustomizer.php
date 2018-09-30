<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Installer\Config\Plugin;

use Shlinkio\Shlink\Installer\Model\CustomizableAppConfig;
use Symfony\Component\Console\Style\SymfonyStyle;

class LanguageConfigCustomizer implements ConfigCustomizerInterface
{
    private const SUPPORTED_LANGUAGES = ['en', 'es'];

    public function process(SymfonyStyle $io, CustomizableAppConfig $appConfig): void
    {
        $io->title('LANGUAGE');

        if ($appConfig->hasLanguage() && $io->confirm('Do you want to keep imported language?')) {
            return;
        }

        $appConfig->setLanguage([
            'DEFAULT' => $this->chooseLanguage($io, 'Select default language for the application in general'),
            'CLI' => $this->chooseLanguage($io, 'Select default language for CLI executions'),
        ]);
    }

    private function chooseLanguage(SymfonyStyle $io, string $message): string
    {
        return $io->choice($message, self::SUPPORTED_LANGUAGES, self::SUPPORTED_LANGUAGES[0]);
    }
}
