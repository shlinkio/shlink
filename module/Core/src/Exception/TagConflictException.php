<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use function sprintf;

class TagConflictException extends RuntimeException
{
    public static function fromExistingTag(string $oldName, string $newName): self
    {
        return new self(sprintf('You cannot rename tag %s to %s, because it already exists', $oldName, $newName));
    }
}
