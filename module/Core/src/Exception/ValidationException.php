<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Throwable;
use Zend\InputFilter\InputFilterInterface;

use function Functional\reduce_left;
use function is_array;
use function print_r;
use function sprintf;

use const PHP_EOL;

class ValidationException extends RuntimeException
{
    /** @var array */
    private $invalidElements;

    public function __construct(
        string $message = '',
        array $invalidElements = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $this->invalidElements = $invalidElements;
        parent::__construct($message, $code, $previous);
    }

    public static function fromInputFilter(InputFilterInterface $inputFilter, ?Throwable $prev = null): self
    {
        return static::fromArray($inputFilter->getMessages(), $prev);
    }

    private static function fromArray(array $invalidData, ?Throwable $prev = null): self
    {
        return new self(
            sprintf(
                'Provided data is not valid. These are the messages:%s%s%s',
                PHP_EOL,
                self::formMessagesToString($invalidData),
                PHP_EOL
            ),
            $invalidData,
            -1,
            $prev
        );
    }

    private static function formMessagesToString(array $messages = []): string
    {
        return reduce_left($messages, function ($messageSet, $name, $_, string $acc) {
            return $acc . sprintf(
                "\n    '%s' => %s",
                $name,
                is_array($messageSet) ? print_r($messageSet, true) : $messageSet
            );
        }, '');
    }

    public function getInvalidElements(): array
    {
        return $this->invalidElements;
    }
}
