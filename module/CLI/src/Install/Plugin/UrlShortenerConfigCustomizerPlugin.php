<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Install\Plugin;

use Shlinkio\Shlink\CLI\Model\CustomizableAppConfig;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UrlShortenerConfigCustomizerPlugin extends AbstractConfigCustomizerPlugin
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param CustomizableAppConfig $appConfig
     * @return void
     * @throws RuntimeException
     */
    public function process(InputInterface $input, OutputInterface $output, CustomizableAppConfig $appConfig)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('URL SHORTENER');

        if ($appConfig->hasUrlShortener() && $io->confirm(
            '<question>Do you want to keep imported URL shortener config? (Y/n):</question> '
        )) {
            return;
        }

        // Ask for URL shortener params
        $appConfig->setUrlShortener([
            'SCHEMA' => $io->choice(
                '<question>Select schema for generated short URLs (defaults to http):</question>',
                ['http', 'https'],
                0
            ),
            'HOSTNAME' => $this->ask($io, 'Hostname for generated URLs'),
            'CHARS' => $this->ask(
                $io,
                'Character set for generated short codes (leave empty to autogenerate one)',
                null,
                true
            ) ?: str_shuffle(UrlShortener::DEFAULT_CHARS),
            'VALIDATE_URL' => $io->confirm(
                '<question>Do you want to validate long urls by 200 HTTP status code on response (Y/n):</question>'
            ),
        ]);
    }
}
