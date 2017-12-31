<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Install\Plugin;

use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractConfigCustomizerPlugin implements ConfigCustomizerPluginInterface
{
    /**
     * @param SymfonyStyle $io
     * @param string $text
     * @param string|null $default
     * @param bool $allowEmpty
     * @return string
     */
    protected function ask(SymfonyStyle $io, $text, $default = null, $allowEmpty = false): string
    {
        if ($default !== null) {
            $text .= ' (defaults to ' . $default . ')';
        }
        do {
            $value = $io->ask($text, $default);
            if (empty($value) && ! $allowEmpty) {
                $io->writeln('<error>Value can\'t be empty</error>');
            }
        } while (empty($value) && $default === null && ! $allowEmpty);

        return $value;
    }
}
