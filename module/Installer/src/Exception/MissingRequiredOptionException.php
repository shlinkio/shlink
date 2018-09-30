<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Installer\Exception;

use RuntimeException;
use function sprintf;

class MissingRequiredOptionException extends RuntimeException implements ExceptionInterface
{
    public static function fromOption(string $optionName): self
    {
        return new self(sprintf('The "%s" is required and can\'t be empty', $optionName));
    }
}
