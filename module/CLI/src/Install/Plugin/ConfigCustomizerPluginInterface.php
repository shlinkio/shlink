<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Install\Plugin;

use Shlinkio\Shlink\CLI\Model\CustomizableAppConfig;
use Symfony\Component\Console\Style\SymfonyStyle;

interface ConfigCustomizerPluginInterface
{
    /**
     * @param SymfonyStyle $io
     * @param CustomizableAppConfig $appConfig
     * @return void
     */
    public function process(SymfonyStyle $io, CustomizableAppConfig $appConfig);
}
