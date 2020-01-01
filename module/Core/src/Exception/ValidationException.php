<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Throwable;
use Zend\InputFilter\InputFilterInterface;
use Zend\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Zend\ProblemDetails\Exception\ProblemDetailsExceptionInterface;

use function array_keys;
use function Functional\reduce_left;
use function is_array;
use function print_r;
use function sprintf;

use const PHP_EOL;

class ValidationException extends InvalidArgumentException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    private const TITLE = 'Invalid data';
    private const TYPE = 'INVALID_ARGUMENT';

    private array $invalidElements;

    public static function fromInputFilter(InputFilterInterface $inputFilter, ?Throwable $prev = null): self
    {
        return static::fromArray($inputFilter->getMessages(), $prev);
    }

    public static function fromArray(array $invalidData, ?Throwable $prev = null): self
    {
        $status = StatusCodeInterface::STATUS_BAD_REQUEST;
        $e = new self('Provided data is not valid', $status, $prev);

        $e->detail = $e->getMessage();
        $e->title = self::TITLE;
        $e->type = self::TYPE;
        $e->status = StatusCodeInterface::STATUS_BAD_REQUEST;
        $e->invalidElements = $invalidData;
        $e->additional = ['invalidElements' => array_keys($invalidData)];

        return $e;
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
            $this->getTraceAsString(),
        );
    }

    private function invalidElementsToString(): string
    {
        return reduce_left($this->getInvalidElements(), fn ($messages, string $name, $_, string $acc) => $acc . sprintf(
            "\n    '%s' => %s",
            $name,
            is_array($messages) ? print_r($messages, true) : $messages,
        ), '');
    }
}
