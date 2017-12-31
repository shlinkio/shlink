<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Install\Plugin;

use Shlinkio\Shlink\CLI\Model\CustomizableAppConfig;
use Shlinkio\Shlink\Common\Util\StringUtilsTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ApplicationConfigCustomizerPlugin extends AbstractConfigCustomizerPlugin
{
    use StringUtilsTrait;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param CustomizableAppConfig $appConfig
     * @return void
     */
    public function process(InputInterface $input, OutputInterface $output, CustomizableAppConfig $appConfig)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('APPLICATION');

        if ($appConfig->hasApp() && $io->confirm('Do you want to keep imported application config?')) {
            return;
        }

        $appConfig->setApp([
            'SECRET' => $io->ask(
                'Define a secret string that will be used to sign API tokens (leave empty to autogenerate one)',
                null,
                function ($value) {
                    return $value;
                }
            ) ?: $this->generateRandomString(32),
        ]);
    }
}
