<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Config;

use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;
use function str_shuffle;

/** @deprecated */
class GenerateCharsetCommand extends Command
{
    public const NAME = 'config:generate-charset';
    private const DEFAULT_CHARS = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription(sprintf(
                '[DEPRECATED] Generates a character set sample just by shuffling the default one, "%s". '
                . 'Then it can be set in the SHORTCODE_CHARS environment variable',
                self::DEFAULT_CHARS
            ))
            ->setHelp('<fg=red;options=bold>This command is deprecated. Better leave shlink generate the charset.</>');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $charSet = str_shuffle(self::DEFAULT_CHARS);
        (new SymfonyStyle($input, $output))->success(sprintf('Character set: "%s"', $charSet));
        return ExitCodes::EXIT_SUCCESS;
    }
}
