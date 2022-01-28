<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\InputFilter\InputFilterInterface;
use Mezzio\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;
use Throwable;

use function array_keys;
use function Shlinkio\Shlink\Core\arrayToString;
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

        // TODO Expose reasons for the validation to fail
        // $e->additional = ['invalidElements' => array_keys($invalidData), 'reasons' => $invalidData];

        return $e;
    }

    public function getInvalidElements(): array
    {
        return $this->invalidElements;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s %s in %s:%s%s%s%sStack trace:%s%s',
            __CLASS__,
            $this->getMessage(),
            $this->getFile(),
            $this->getLine(),
            PHP_EOL,
            arrayToString($this->getInvalidElements()),
            PHP_EOL,
            PHP_EOL,
            $this->getTraceAsString(),
        );
    }
}
