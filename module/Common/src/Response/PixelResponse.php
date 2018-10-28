<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Response;

use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;
use function base64_decode;

class PixelResponse extends Response
{
    private const BASE_64_IMAGE = 'R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw==';
    private const CONTENT_TYPE = 'image/gif';

    public function __construct(int $status = 200, array $headers = [])
    {
        $headers['content-type'] = self::CONTENT_TYPE;
        parent::__construct($this->createBody(), $status, $headers);
    }

    /**
     * Create the message body.
     *
     * @return StreamInterface
     */
    private function createBody(): StreamInterface
    {
        $body = new Stream('php://temp', 'wb+');
        $body->write((string) base64_decode(self::BASE_64_IMAGE));
        $body->rewind();
        return $body;
    }
}
