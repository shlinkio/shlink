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

    public static function fromArray(array $invalidData, ?Throwable $prev = null): self
    {
        return new self('Provided data is not valid', $invalidData, -1, $prev);
    }

    public function getInvalidElements(): array
    {
        return $this->invalidElements;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s %s in %s:%s%s%sStack trace:%s%s',
            __CLASS__,
            $this->getMessage(),
            $this->getFile(),
            $this->getLine(),
            $this->invalidElementsToString(),
            PHP_EOL,
            PHP_EOL,
            $this->getTraceAsString()
        );
    }

    private function invalidElementsToString(): string
    {
        return reduce_left($this->invalidElements, function ($messageSet, string $name, $_, string $acc) {
            return $acc . sprintf(
                "\n    '%s' => %s",
                $name,
                is_array($messageSet) ? print_r($messageSet, true) : $messageSet
            );
        }, '');
    }
}
