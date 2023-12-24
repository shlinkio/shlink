<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Core\Action;

use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class QrCodeTest extends ApiTestCase
{
    #[Test]
    public function returnsNotFoundWhenShortUrlIsNotEnabled(): void
    {
        // The QR code successfully resolves at first
        $response = $this->callShortUrl('custom/qr-code');
        self::assertEquals(200, $response->getStatusCode());

        // This short URL allow max 2 visits
        $this->callShortUrl('custom');
        $this->callShortUrl('custom');

        // After 2 visits, the QR code should return a 404
        $response = $this->callShortUrl('custom/qr-code');
        self::assertEquals(404, $response->getStatusCode());
    }
}
