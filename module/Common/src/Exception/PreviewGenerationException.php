<?php
namespace Shlinkio\Shlink\Common\Exception;

class PreviewGenerationException extends RuntimeException
{
    public static function fromImageError($error)
    {
        return new self(sprintf('Error generating a preview image with error: %s', $error));
    }
}
