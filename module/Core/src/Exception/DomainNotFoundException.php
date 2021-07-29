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

    private function __construct(string $message, array $additional)
    {
        parent::__construct($message);

        $this->detail = $message;
        $this->title = self::TITLE;
        $this->type = self::TYPE;
        $this->status = StatusCodeInterface::STATUS_NOT_FOUND;
        $this->additional = $additional;
    }

    public static function fromId(string $id): self
    {
        return new self(sprintf('Domain with id "%s" could not be found', $id), ['id' => $id]);
    }

    public static function fromAuthority(string $authority): self
    {
        return new self(
            sprintf('Domain with authority "%s" could not be found', $authority),
            ['authority' => $authority],
        );
    }
}
