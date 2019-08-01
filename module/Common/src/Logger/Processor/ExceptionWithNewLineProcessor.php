<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Logger\Processor;

use function str_replace;
use function strpos;

use const PHP_EOL;

final class ExceptionWithNewLineProcessor
{
    private const EXCEPTION_PLACEHOLDER = '{e}';

    public function __invoke(array $record)
    {
        $message = $record['message'];
        $messageHasExceptionPlaceholder = strpos($message, self::EXCEPTION_PLACEHOLDER) !== false;

        if ($messageHasExceptionPlaceholder) {
            $record['message'] = str_replace(
                self::EXCEPTION_PLACEHOLDER,
                PHP_EOL . self::EXCEPTION_PLACEHOLDER,
                $message
            );
        }

        return $record;
    }
}
