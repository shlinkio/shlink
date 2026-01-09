<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Input;

use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function Shlinkio\Shlink\Core\normalizeOptionalDate;
use function sprintf;

final class InputUtils
{
    /**
     * Process a date provided via input params, and format it as ATOM.
     * A warning is printed if the date cannot be parsed, returning `null` in that case.
     */
    public static function processDate(string $name, string|null $value, OutputInterface $output): string|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return normalizeOptionalDate($value)->toAtomString();
        } catch (Throwable) {
            $output->writeln(sprintf(
                '<comment>> Ignored provided "%s" since its value "%s" is not a valid date. <</comment>',
                $name,
                $value,
            ));

            return null;
        }
    }
}
