<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Mezzio\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;

use function sprintf;

class DomainNotFoundException extends DomainException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    private const TITLE = 'Domain not found';
    private const TYPE = 'DOMAIN_NOT_FOUND';

    public static function fromId(string $id): self
    {
        $e = new self(sprintf('Domain with id "%s" could not be found', $id));

        $e->detail = $e->getMessage();
        $e->title = self::TITLE;
        $e->type = self::TYPE;
        $e->status = StatusCodeInterface::STATUS_NOT_FOUND;
        $e->additional = ['id' => $id];

        return $e;
    }
}
