<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Response;

use Endroid\QrCode\QrCode;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

class QrCodeResponse extends Response
{
    use Response\InjectContentTypeTrait;

    public function __construct(QrCode $qrCode, int $status = StatusCode::STATUS_OK, array $headers = [])
    {
        parent::__construct(
            $this->createBody($qrCode),
            $status,
            $this->injectContentType($qrCode->getContentType(), $headers)
        );
    }

    private function createBody(QrCode $qrCode): StreamInterface
    {
        $body = new Stream('php://temp', 'wb+');
        $body->write($qrCode->get());
        $body->rewind();
        return $body;
    }
}
