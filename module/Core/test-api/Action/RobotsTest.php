<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Core\Action;

use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class RobotsTest extends ApiTestCase
{
    #[Test]
    public function expectedListOfCrawlableShortCodesIsReturned(): void
    {
        $resp = $this->callShortUrl('robots.txt');
        $body = $resp->getBody()->__toString();

        self::assertEquals(200, $resp->getStatusCode());
        self::assertEquals(
            <<<ROBOTS
            # For more information about the robots.txt standard, see:
            # https://www.robotstxt.org/orig.html
            
            User-agent: *
            Allow: /custom
            Allow: /abc123
            Disallow: /
            ROBOTS,
            $body,
        );
    }
}
