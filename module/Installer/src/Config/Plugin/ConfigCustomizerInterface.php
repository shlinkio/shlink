<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Installer\Config\Plugin;

use Shlinkio\Shlink\Installer\Model\CustomizableAppConfig;
use Symfony\Component\Console\Style\SymfonyStyle;

interface ConfigCustomizerInterface
{
    public function process(SymfonyStyle $io, CustomizableAppConfig $appConfig): void;
}
