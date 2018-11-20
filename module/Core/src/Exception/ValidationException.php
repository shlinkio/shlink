<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Throwable;
use Zend\InputFilter\InputFilterInterface;
use const PHP_EOL;
use function is_array;
use function print_r;
use function sprintf;

class ValidationException extends RuntimeException
{
    /** @var array */
    private $invalidElements;

    public function __construct(
        string $message = '',
        array $invalidElements = [],
        int $code = 0,
        Throwable $previous = null
    ) {
        $this->invalidElements = $invalidElements;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param InputFilterInterface $inputFilter
     * @param \Throwable|null $prev
     * @return ValidationException
     */
    public static function fromInputFilter(InputFilterInterface $inputFilter, Throwable $prev = null): self
    {
        return static::fromArray($inputFilter->getMessages(), $prev);
    }

    /**
     * @param array $invalidData
     * @param \Throwable|null $prev
     * @return ValidationException
     */
    private static function fromArray(array $invalidData, Throwable $prev = null): self
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

    private static function formMessagesToString(array $messages = [])
    {
        $text = '';
        foreach ($messages as $name => $messageSet) {
            $text .= sprintf(
                "\n\t'%s' => %s",
                $name,
                is_array($messageSet) ? print_r($messageSet, true) : $messageSet
            );
        }

        return $text;
    }

    /**
     * @return array
     */
    public function getInvalidElements(): array
    {
        return $this->invalidElements;
    }
}
