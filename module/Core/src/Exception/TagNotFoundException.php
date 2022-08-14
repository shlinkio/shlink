<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Mezzio\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;

use function Shlinkio\Shlink\Core\toProblemDetailsType;
use function sprintf;

class TagNotFoundException extends DomainException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    private const TITLE = 'Tag not found';
    public const ERROR_CODE = 'tag-not-found';

    public static function fromTag(string $tag): self
    {
        $e = new self(sprintf('Tag with name "%s" could not be found', $tag));

        $e->detail = $e->getMessage();
        $e->title = self::TITLE;
        $e->type = toProblemDetailsType(self::ERROR_CODE);
        $e->status = StatusCodeInterface::STATUS_NOT_FOUND;
        $e->additional = ['tag' => $tag];

        return $e;
    }
}
