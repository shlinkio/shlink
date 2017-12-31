<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Install\Plugin;

use Shlinkio\Shlink\CLI\Model\CustomizableAppConfig;
use Symfony\Component\Console\Style\SymfonyStyle;

class LanguageConfigCustomizerPlugin extends AbstractConfigCustomizerPlugin
{
    const SUPPORTED_LANGUAGES = ['en', 'es'];

    /**
     * @param SymfonyStyle $io
     * @param CustomizableAppConfig $appConfig
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
