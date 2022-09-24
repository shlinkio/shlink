<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Visit\Model;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Options\TrackingOptions;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;

use function random_int;
use function str_repeat;
use function strlen;
use function substr;

class VisitorTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideParams
     */
    public function providedFieldsValuesAreCropped(array $params, array $expected): void
    {
        $visitor = new Visitor(...$params);
        ['userAgent' => $userAgent, 'referer' => $referer, 'remoteAddress' => $remoteAddress] = $expected;

        self::assertEquals($userAgent, $visitor->userAgent);
        self::assertEquals($referer, $visitor->referer);
        self::assertEquals($remoteAddress, $visitor->remoteAddress);
    }

    public function provideParams(): iterable
    {
        yield 'all values are bigger' => [
            [str_repeat('a', 1000), str_repeat('b', 2000), str_repeat('c', 500), ''],
            [
                'userAgent' => str_repeat('a', Visitor::USER_AGENT_MAX_LENGTH),
                'referer' => str_repeat('b', Visitor::REFERER_MAX_LENGTH),
                'remoteAddress' => str_repeat('c', Visitor::REMOTE_ADDRESS_MAX_LENGTH),
            ],
        ];
        yield 'some values are smaller' => [
            [str_repeat('a', 10), str_repeat('b', 2000), null, ''],
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
                '',
            ],
            [
                'userAgent' => substr($userAgent, 0, Visitor::USER_AGENT_MAX_LENGTH),
                'referer' => $referer,
                'remoteAddress' => null,
            ],
        ];
    }

    private function generateRandomString(int $length): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /** @test */
    public function newNormalizedInstanceIsCreatedFromTrackingOptions(): void
    {
        $visitor = new Visitor(
            $this->generateRandomString(2000),
            $this->generateRandomString(2000),
            $this->generateRandomString(2000),
            $this->generateRandomString(2000),
        );
        $normalizedVisitor = $visitor->normalizeForTrackingOptions(new TrackingOptions(
            disableIpTracking: true,
            disableReferrerTracking: true,
            disableUaTracking: true,
        ));

        self::assertNotSame($visitor, $normalizedVisitor);
        self::assertEmpty($normalizedVisitor->userAgent);
        self::assertNotEmpty($visitor->userAgent);
        self::assertEmpty($normalizedVisitor->referer);
        self::assertNotEmpty($visitor->referer);
        self::assertNull($normalizedVisitor->remoteAddress);
        self::assertNotNull($visitor->remoteAddress);
    }
}
