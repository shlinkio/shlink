<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Response;

use Endroid\QrCode\QrCode;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Response\QrCodeResponse;

class QrCodeResponseTest extends TestCase
{
    /**
     * @test
     */
    public function providedQrCoideIsSetAsBody()
    {
        $qrCode = new QrCode('Hello');
        $resp = new QrCodeResponse($qrCode);

        $this->assertEquals($qrCode->getContentType(), $resp->getHeaderLine('Content-Type'));
        $this->assertEquals($qrCode->get(), (string) $resp->getBody());
    }
}
