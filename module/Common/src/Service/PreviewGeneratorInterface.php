<?php
namespace Shlinkio\Shlink\Common\Service;

use Shlinkio\Shlink\Common\Exception\PreviewGenerationException;

interface PreviewGeneratorInterface
{
    /**
     * Generates and stores preview for provided website and returns the path to the image file
     *
     * @param string $url
     * @return string
     * @throws PreviewGenerationException
     */
    public function generatePreview($url);
}
