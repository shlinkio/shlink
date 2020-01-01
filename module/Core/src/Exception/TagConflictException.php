<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Mezzio\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;

use function sprintf;

class TagConflictException extends RuntimeException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    private const TITLE = 'Tag conflict';
    private const TYPE = 'TAG_CONFLICT';

    public static function fromExistingTag(string $oldName, string $newName): self
    {
        $e = new self(sprintf('You cannot rename tag %s to %s, because it already exists', $oldName, $newName));

        $e->detail = $e->getMessage();
        $e->title = self::TITLE;
        $e->type = self::TYPE;
        $e->status = StatusCodeInterface::STATUS_CONFLICT;
        $e->additional = [
            'oldName' => $oldName,
            'newName' => $newName,
        ];

        return $e;
    }
}
