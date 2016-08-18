<?php
namespace Shlinkio\Shlink\Common\Service;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Doctrine\Common\Cache\Cache;
use mikehaertl\wkhtmlto\Image;

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
     * @Inject({Image::class, Cache::class, "config.phpwkhtmltopdf.images.files_location"})
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
     */
    public function generatePreview($url)
    {
        $cacheId = sprintf('preview_%s.png', urlencode($url));
        if ($this->cache->contains($cacheId)) {
            return $this->cache->fetch($cacheId);
        }

        $path = $this->location . '/' . $cacheId;
        $this->image->setPage($url);
        $this->image->saveAs($path);
        $this->cache->save($cacheId, $path);

        return $path;
    }
}
