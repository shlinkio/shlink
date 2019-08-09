<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Service;

use Shlinkio\Shlink\Common\Exception\PreviewGenerationException;

/** @deprecated */
interface PreviewGeneratorInterface
{
    /**
     * Generates and stores preview for provided website and returns the path to the image file
     *
     * @throws PreviewGenerationException
     */
    public function generatePreview(string $url): string;
}
