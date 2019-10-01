<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use function sprintf;

class NonUniqueSlugException extends InvalidArgumentException
{
    public static function fromSlug(string $slug, ?string $domain): self
    {
        $suffix = '';
        if ($domain !== null) {
            $suffix = sprintf(' for domain "%s"', $domain);
        }

        return new self(sprintf('Provided slug "%s" is not unique%s.', $slug, $suffix));
    }
}
