<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Response;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
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
        return new Response($body, StatusCode::STATUS_OK, ArrayUtils::merge([
            'Content-Type' => (new finfo(FILEINFO_MIME))->file($path),
            'Content-Length' => (string) $body->getSize(),
        ], $extraHeaders));
    }
}
