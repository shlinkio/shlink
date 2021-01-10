<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Mezzio\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;
use Shlinkio\Shlink\Core\Tag\Model\TagRenaming;

use function sprintf;

class TagConflictException extends RuntimeException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    private const TITLE = 'Tag conflict';
    private const TYPE = 'TAG_CONFLICT';

    public static function forExistingTag(TagRenaming $renaming): self
    {
        $e = new self(sprintf('You cannot rename tag %s, because it already exists', $renaming->toString()));

        $e->detail = $e->getMessage();
        $e->title = self::TITLE;
        $e->type = self::TYPE;
        $e->status = StatusCodeInterface::STATUS_CONFLICT;
        $e->additional = $renaming->toArray();

        return $e;
    }
}
