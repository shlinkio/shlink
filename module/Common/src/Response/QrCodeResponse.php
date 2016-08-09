<?php
namespace Shlinkio\Shlink\Common\Response;

use Endroid\QrCode\QrCode;
use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

class QrCodeResponse extends Response
{
    use Response\InjectContentTypeTrait;

    public function __construct(QrCode $qrCode, $status = 200, array $headers = [])
    {
        parent::__construct(
            $this->createBody($qrCode),
            $status,
            $this->injectContentType($qrCode->getContentType(), $headers)
        );
    }

    /**
     * Create the message body.
     *
     * @param QrCode $qrCode
     * @return StreamInterface
     */
    private function createBody(QrCode $qrCode)
    {
        $body = new Stream('php://temp', 'wb+');
        $body->write($qrCode->get());
        $body->rewind();
        return $body;
    }
}
