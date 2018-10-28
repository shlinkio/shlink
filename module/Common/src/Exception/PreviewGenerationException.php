<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Exception;

use function sprintf;

class PreviewGenerationException extends RuntimeException
{
    public static function fromImageError($error)
    {
        return new self(sprintf('Error generating a preview image with error: %s', $error));
    }
}
