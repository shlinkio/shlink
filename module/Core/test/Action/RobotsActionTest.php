<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Action;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Action\RobotsAction;
use Shlinkio\Shlink\Core\Crawling\CrawlingHelperInterface;
use Shlinkio\Shlink\Core\Options\RobotsOptions;

class RobotsActionTest extends TestCase
{
    private MockObject & CrawlingHelperInterface $helper;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(CrawlingHelperInterface::class);
    }

    #[Test, DataProvider('provideShortCodes')]
    public function buildsRobotsLinesFromCrawlableShortCodes(
        array $shortCodes,
        RobotsOptions $options,
        string $expected,
    ): void {
        $this->helper
            ->expects($options->allowAllShortUrls ? $this->never() : $this->once())
            ->method('listCrawlableShortCodes')
            ->willReturn($shortCodes);

        $response = $this->action($options)->handle(ServerRequestFactory::fromGlobals());

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($expected, $response->getBody()->__toString());
        self::assertEquals('text/plain', $response->getHeaderLine('Content-Type'));
    }

    public static function provideShortCodes(): iterable
    {
        yield 'three short codes' => [['foo', 'bar', 'baz'], new RobotsOptions(), <<<ROBOTS
        # For more information about the robots.txt standard, see:
        # https://www.robotstxt.org/orig.html

        User-agent: *
        Allow: /foo
        Allow: /bar
        Allow: /baz
        Disallow: /
        ROBOTS];
        yield 'five short codes' => [['foo', 'bar', 'some', 'thing', 'baz'], new RobotsOptions(), <<<ROBOTS
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
        yield 'no short codes' => [[], new RobotsOptions(), <<<ROBOTS
        # For more information about the robots.txt standard, see:
        # https://www.robotstxt.org/orig.html

        User-agent: *
        Disallow: /
        ROBOTS];
        yield 'three short codes and allow all short urls' => [
            ['foo', 'bar', 'some'],
            new RobotsOptions(allowAllShortUrls: true),
            <<<ROBOTS
            # For more information about the robots.txt standard, see:
            # https://www.robotstxt.org/orig.html

            User-agent: *
            Disallow: /rest/
            ROBOTS,
        ];
        yield 'no short codes and allow all short urls' => [[], new RobotsOptions(allowAllShortUrls: true), <<<ROBOTS
        # For more information about the robots.txt standard, see:
        # https://www.robotstxt.org/orig.html

        User-agent: *
        Disallow: /rest/
        ROBOTS];
        yield 'allow user agents' => [[], new RobotsOptions(userAgents: ['foo', 'bar']), <<<ROBOTS
        # For more information about the robots.txt standard, see:
        # https://www.robotstxt.org/orig.html

        User-agent: foo
        User-agent: bar
        Disallow: /
        ROBOTS];
    }

    private function action(RobotsOptions $options): RobotsAction
    {
        return new RobotsAction($this->helper, $options);
    }
}
