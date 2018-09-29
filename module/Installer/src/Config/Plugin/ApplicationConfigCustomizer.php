<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Installer\Config\Plugin;

use Shlinkio\Shlink\Common\Util\StringUtilsTrait;
use Shlinkio\Shlink\Installer\Model\CustomizableAppConfig;
use Symfony\Component\Console\Style\SymfonyStyle;

class ApplicationConfigCustomizer implements ConfigCustomizerInterface
{
    use StringUtilsTrait;

    /**
     * @param SymfonyStyle $io
     * @param CustomizableAppConfig $appConfig
     * @return void
     */
    public function process(SymfonyStyle $io, CustomizableAppConfig $appConfig)
    {
        $io->title('APPLICATION');

        if ($appConfig->hasApp() && $io->confirm('Do you want to keep imported application config?')) {
            return;
        }

        $validator = function ($value) {
            return $value;
        };
        $appConfig->setApp([
            'SECRET' => $io->ask(
                'Define a secret string that will be used to sign API tokens (leave empty to autogenerate one)',
                null,
                $validator
            ) ?: $this->generateRandomString(32),
            'DISABLE_TRACK_PARAM' => $io->ask(
                'Provide a parameter name that you will be able to use to disable tracking on specific request to '
                . 'short URLs (leave empty and this feature won\'t be enabled)',
                null,
                $validator
            ),
        ]);
    }
}
