<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Action;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Action\RobotsAction;
use Shlinkio\Shlink\Core\Crawling\CrawlingHelperInterface;

class RobotsActionTest extends TestCase
{
    private RobotsAction $action;
    private MockObject & CrawlingHelperInterface $helper;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(CrawlingHelperInterface::class);
        $this->action = new RobotsAction($this->helper);
    }

    /**
     * @test
     * @dataProvider provideShortCodes
     */
    public function buildsRobotsLinesFromCrawlableShortCodes(array $shortCodes, string $expected): void
    {
        $this->helper
            ->expects($this->once())
            ->method('listCrawlableShortCodes')
            ->willReturn($shortCodes);

        $response = $this->action->handle(ServerRequestFactory::fromGlobals());

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($expected, $response->getBody()->__toString());
        self::assertEquals('text/plain', $response->getHeaderLine('Content-Type'));
    }

    public static function provideShortCodes(): iterable
    {
        yield 'three short codes' => [['foo', 'bar', 'baz'], <<<ROBOTS
        # For more information about the robots.txt standard, see:
        # https://www.robotstxt.org/orig.html

        User-agent: *
        Allow: /foo
        Allow: /bar
        Allow: /baz
        Disallow: /
        ROBOTS];
        yield 'five short codes' => [['foo', 'bar', 'some', 'thing', 'baz'], <<<ROBOTS
        # For more information about the robots.txt standard, see:
        # https://www.robotstxt.org/orig.html

        User-agent: *
        Allow: /foo
        Allow: /bar
        Allow: /some
        Allow: /thing
        Allow: /baz
        Disallow: /
        ROBOTS];
        yield 'no short codes' => [[], <<<ROBOTS
        # For more information about the robots.txt standard, see:
        # https://www.robotstxt.org/orig.html

        User-agent: *
        Disallow: /
        ROBOTS];
    }
}
