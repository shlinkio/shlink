<?php
namespace Shlinkio\Shlink\Common\Service;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Doctrine\Common\Cache\Cache;
use mikehaertl\wkhtmlto\Image;
use Shlinkio\Shlink\Common\Exception\PreviewGenerationException;

class PreviewGenerator implements PreviewGeneratorInterface
{
    /**
     * @var Image
     */
    private $image;
    /**
     * @var Cache
     */
    private $cache;
    /**
     * @var string
     */
    private $location;

    /**
     * PreviewGenerator constructor.
     * @param Image $image
     * @param Cache $cache
     * @param string $location
     *
     * @Inject({Image::class, Cache::class, "config.phpwkhtmltopdf.files_location"})
     */
    public function __construct(Image $image, Cache $cache, $location)
    {
        $this->image = $image;
        $this->cache = $cache;
        $this->location = $location;
    }

    /**
     * Generates and stores preview for provided website and returns the path to the image file
     *
     * @param string $url
     * @return string
     * @throws PreviewGenerationException
     */
    public function generatePreview($url)
    {
        $cacheId = sprintf('preview_%s.%s', urlencode($url), $this->image->type);
        if ($this->cache->contains($cacheId)) {
            return $this->cache->fetch($cacheId);
        }

        $path = $this->location . '/' . $cacheId;
        $this->image->setPage($url);
        $this->image->saveAs($path);

        // Check if an error occurred
        $error = $this->image->getError();
        if (! empty($error)) {
            throw PreviewGenerationException::fromImageError($error);
        }

        // Cache the path and return it
        $this->cache->save($cacheId, $path);
        return $path;
    }
}
