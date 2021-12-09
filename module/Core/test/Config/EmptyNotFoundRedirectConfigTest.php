<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Config;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\EmptyNotFoundRedirectConfig;

class EmptyNotFoundRedirectConfigTest extends TestCase
{
    private EmptyNotFoundRedirectConfig $redirectsConfig;

    protected function setUp(): void
    {
        $this->redirectsConfig = new EmptyNotFoundRedirectConfig();
    }

    /** @test */
    public function allMethodsReturnHardcodedValues(): void
    {
        self::assertNull($this->redirectsConfig->invalidShortUrlRedirect());
        self::assertFalse($this->redirectsConfig->hasInvalidShortUrlRedirect());
        self::assertNull($this->redirectsConfig->regular404Redirect());
        self::assertFalse($this->redirectsConfig->hasRegular404Redirect());
        self::assertNull($this->redirectsConfig->baseUrlRedirect());
        self::assertFalse($this->redirectsConfig->hasBaseUrlRedirect());
    }
}
