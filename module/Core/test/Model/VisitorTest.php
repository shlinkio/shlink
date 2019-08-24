<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Model;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Util\StringUtilsTrait;
use Shlinkio\Shlink\Core\Model\Visitor;

use function str_repeat;
use function substr;

class VisitorTest extends TestCase
{
    use StringUtilsTrait;

    /**
     * @test
     * @dataProvider provideParams
     */
    public function providedFieldsValuesAreCropped(array $params, array $expected): void
    {
        $visitor = new Visitor(...$params);
        ['userAgent' => $userAgent, 'referer' => $referer, 'remoteAddress' => $remoteAddress] = $expected;

        $this->assertEquals($userAgent, $visitor->getUserAgent());
        $this->assertEquals($referer, $visitor->getReferer());
        $this->assertEquals($remoteAddress, $visitor->getRemoteAddress());
    }

    public function provideParams(): iterable
    {
        yield 'all values are bigger' => [
            [str_repeat('a', 1000), str_repeat('b', 2000), str_repeat('c', 500)],
            [
                'userAgent' => str_repeat('a', Visitor::USER_AGENT_MAX_LENGTH),
                'referer' => str_repeat('b', Visitor::REFERER_MAX_LENGTH),
                'remoteAddress' => str_repeat('c', Visitor::REMOTE_ADDRESS_MAX_LENGTH),
            ],
        ];
        yield 'some values are smaller' => [
            [str_repeat('a', 10), str_repeat('b', 2000), null],
            [
                'userAgent' => str_repeat('a', 10),
                'referer' => str_repeat('b', Visitor::REFERER_MAX_LENGTH),
                'remoteAddress' => null,
            ],
        ];
        yield 'random strings' => [
            [
                $userAgent = $this->generateRandomString(2000),
                $referer = $this->generateRandomString(50),
                null,
            ],
            [
                'userAgent' => substr($userAgent, 0, Visitor::USER_AGENT_MAX_LENGTH),
                'referer' => $referer,
                'remoteAddress' => null,
            ],
        ];
    }
}
