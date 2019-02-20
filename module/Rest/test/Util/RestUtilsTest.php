<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Util;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Common\Exception\WrongIpException;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Rest\Exception\AuthenticationException;
use Shlinkio\Shlink\Rest\Util\RestUtils;

class RestUtilsTest extends TestCase
{
    /** @test */
    public function correctCodeIsReturnedFromException()
    {
        $this->assertEquals(
            RestUtils::INVALID_SHORTCODE_ERROR,
            RestUtils::getRestErrorCodeFromException(new InvalidShortCodeException())
        );
        $this->assertEquals(
            RestUtils::INVALID_URL_ERROR,
            RestUtils::getRestErrorCodeFromException(new InvalidUrlException())
        );
        $this->assertEquals(
            RestUtils::INVALID_ARGUMENT_ERROR,
            RestUtils::getRestErrorCodeFromException(new InvalidArgumentException())
        );
        $this->assertEquals(
            RestUtils::INVALID_CREDENTIALS_ERROR,
            RestUtils::getRestErrorCodeFromException(new AuthenticationException())
        );
        $this->assertEquals(
            RestUtils::UNKNOWN_ERROR,
            RestUtils::getRestErrorCodeFromException(new WrongIpException())
        );
    }
}
