<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Installer\Util;

use Shlinkio\Shlink\Installer\Exception\MissingRequiredOptionException;
use Symfony\Component\Console\Style\SymfonyStyle;

trait AskUtilsTrait
{
    /**
     * @return mixed
     */
    private function askRequired(SymfonyStyle $io, string $optionName, string $question)
    {
        return $io->ask($question, null, function ($value) use ($optionName) {
            if (empty($value)) {
                throw MissingRequiredOptionException::fromOption($optionName);
            };

            return $value;
        });
    }
}
