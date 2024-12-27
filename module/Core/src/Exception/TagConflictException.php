<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Mezzio\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;
use Shlinkio\Shlink\Core\Model\Renaming;

use function Shlinkio\Shlink\Core\toProblemDetailsType;
use function sprintf;

class TagConflictException extends RuntimeException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    private const string TITLE = 'Tag conflict';
    public const string ERROR_CODE = 'tag-conflict';

    public static function forExistingTag(Renaming $renaming): self
    {
        $e = new self(sprintf('You cannot rename tag %s, because it already exists', $renaming->toString()));

        $e->detail = $e->getMessage();
        $e->title = self::TITLE;
        $e->type = toProblemDetailsType(self::ERROR_CODE);
        $e->status = StatusCodeInterface::STATUS_CONFLICT;
        $e->additional = $renaming->toArray();

        return $e;
    }
}
