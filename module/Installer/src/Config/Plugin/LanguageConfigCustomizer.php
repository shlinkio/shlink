<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Installer\Config\Plugin;

use Shlinkio\Shlink\Installer\Config\Plugin\ConfigCustomizerInterface;
use Shlinkio\Shlink\Installer\Model\CustomizableAppConfig;
use Symfony\Component\Console\Style\SymfonyStyle;

class LanguageConfigCustomizer implements ConfigCustomizerInterface
{
    private const SUPPORTED_LANGUAGES = ['en', 'es'];

    /**
     * @param SymfonyStyle $io
     * @param \Shlinkio\Shlink\Installer\Model\CustomizableAppConfig $appConfig
     * @return void
     */
    public function process(SymfonyStyle $io, CustomizableAppConfig $appConfig)
    {
        $io->title('LANGUAGE');

        if ($appConfig->hasLanguage() && $io->confirm('Do you want to keep imported language?')) {
            return;
        }

        $appConfig->setLanguage([
            'DEFAULT' => $this->chooseLanguage('Select default language for the application in general', $io),
            'CLI' => $this->chooseLanguage('Select default language for CLI executions', $io),
        ]);
    }

    private function chooseLanguage(string $message, SymfonyStyle $io): string
    {
        return $io->choice($message, self::SUPPORTED_LANGUAGES, self::SUPPORTED_LANGUAGES[0]);
    }
}
