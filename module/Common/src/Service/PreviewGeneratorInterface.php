<?php
namespace Shlinkio\Shlink\Common\Service;

interface PreviewGeneratorInterface
{
    /**
     * Generates and stores preview for provided website and returns the path to the image file
     *
     * @param string $url
     * @return string
     */
    public function generatePreview($url);
}
