<?php
namespace Shlinkio\Shlink\Common\Service;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Doctrine\Common\Cache\Cache;
use mikehaertl\wkhtmlto\Image;
use Shlinkio\Shlink\Common\Exception\PreviewGenerationException;
use Shlinkio\Shlink\Common\Image\ImageBuilder;
use Shlinkio\Shlink\Common\Image\ImageBuilderInterface;

class PreviewGenerator implements PreviewGeneratorInterface
{
    /**
     * @var Cache
     */
    private $cache;
    /**
     * @var string
     */
    private $location;
    /**
     * @var ImageBuilderInterface
     */
    private $imageBuilder;

    /**
     * PreviewGenerator constructor.
     * @param ImageBuilderInterface $imageBuilder
     * @param Cache $cache
     * @param string $location
     *
     * @Inject({ImageBuilder::class, Cache::class, "config.preview_generation.files_location"})
     */
    public function __construct(ImageBuilderInterface $imageBuilder, Cache $cache, $location)
    {
        $this->cache = $cache;
        $this->location = $location;
        $this->imageBuilder = $imageBuilder;
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
        /** @var Image $image */
        $image = $this->imageBuilder->build(Image::class, ['url' => $url]);

        $cacheId = sprintf('preview_%s.%s', urlencode($url), $image->type);
        if ($this->cache->contains($cacheId)) {
            return $this->cache->fetch($cacheId);
        }

        $path = $this->location . '/' . $cacheId;
        $image->saveAs($path);

        // Check if an error occurred
        $error = $image->getError();
        if (! empty($error)) {
            throw PreviewGenerationException::fromImageError($error);
        }

        // Cache the path and return it
        $this->cache->save($cacheId, $path);
        return $path;
    }
}
