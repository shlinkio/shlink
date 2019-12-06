<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use function sprintf;

class TagConflictException extends RuntimeException
{
    public static function fromExistingTag(string $tag): self
    {
        return new self(sprintf('Tag with name %s already exists', $tag));
    }
}
