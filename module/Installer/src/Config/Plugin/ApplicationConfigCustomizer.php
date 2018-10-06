<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Installer\Config\Plugin;

use Shlinkio\Shlink\Common\Util\StringUtilsTrait;
use Shlinkio\Shlink\Installer\Model\CustomizableAppConfig;
use Symfony\Component\Console\Style\SymfonyStyle;
use function array_diff;
use function array_keys;

class ApplicationConfigCustomizer implements ConfigCustomizerInterface
{
    use StringUtilsTrait;

    private const SECRET = 'SECRET';
    private const DISABLE_TRACK_PARAM = 'DISABLE_TRACK_PARAM';
    private const EXPECTED_KEYS = [
        self::SECRET,
        self::DISABLE_TRACK_PARAM,
    ];

    public function process(SymfonyStyle $io, CustomizableAppConfig $appConfig): void
    {
        $app = $appConfig->getApp();
        $keysToAskFor = $appConfig->hasApp() ? array_diff(self::EXPECTED_KEYS, array_keys($app)) : self::EXPECTED_KEYS;

        if (empty($keysToAskFor)) {
            return;
        }

        $io->title('APPLICATION');
        foreach ($keysToAskFor as $key) {
            $app[$key] = $this->ask($io, $key);
        }
        $appConfig->setApp($app);
    }

    private function ask(SymfonyStyle $io, string $key)
    {
        switch ($key) {
            case self::SECRET:
                return $io->ask(
                    'Define a secret string that will be used to sign API tokens (leave empty to autogenerate one) '
                    . '<fg=red>[DEPRECATED. TO BE REMOVED]</>'
                ) ?: $this->generateRandomString(32);
            case self::DISABLE_TRACK_PARAM:
                return $io->ask(
                    'Provide a parameter name that you will be able to use to disable tracking on specific request to '
                    . 'short URLs (leave empty and this feature won\'t be enabled)'
                );
        }

        return '';
    }
}
