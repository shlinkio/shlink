<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Response;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Response\PixelResponse;

class PixelResponseTest extends TestCase
{
    /** @var PixelResponse */
    private $resp;

    public function setUp(): void
    {
        $this->resp = new PixelResponse();
    }

    /**
     * @test
     */
    public function responseHasGifTypeAndIsNotEmpty()
    {
        $this->assertEquals('image/gif', $this->resp->getHeaderLine('Content-Type'));
        $this->assertNotEmpty((string) $this->resp->getBody());
    }
}
