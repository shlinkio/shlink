<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service\ShortUrl;

use Shlinkio\Shlink\Core\Exception;

interface DeleteShortUrlServiceInterface
{
    /**
     * @throws Exception\InvalidShortCodeException
     * @throws Exception\DeleteShortUrlException
     */
    public function deleteByShortCode(string $shortCode): void;
}
