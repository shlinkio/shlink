<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Util;

use finfo;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;
use Zend\Stdlib\ArrayUtils;
use const FILEINFO_MIME;

trait ResponseUtilsTrait
{
    private function generateImageResponse(string $imagePath): ResponseInterface
    {
        return $this->generateBinaryResponse($imagePath);
    }

    private function generateBinaryResponse(string $path, array $extraHeaders = []): ResponseInterface
    {
        $body = new Stream($path);
        return new Response($body, 200, ArrayUtils::merge([
            'Content-Type' => (new finfo(FILEINFO_MIME))->file($path),
            'Content-Length' => (string) $body->getSize(),
        ], $extraHeaders));
    }
}
