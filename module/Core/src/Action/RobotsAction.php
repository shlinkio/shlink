<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Crawling\CrawlingHelperInterface;

use function sprintf;

use const PHP_EOL;

class RobotsAction implements RequestHandlerInterface, StatusCodeInterface
{
    public function __construct(private CrawlingHelperInterface $crawlingHelper)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // @phpstan-ignore-next-line The "Response" phpdoc is wrong
        return new Response(self::STATUS_OK, ['Content-type' => 'text/plain'], $this->buildRobots());
    }

    private function buildRobots(): iterable
    {
        yield <<<ROBOTS
        # For more information about the robots.txt standard, see:
        # https://www.robotstxt.org/orig.html

        User-agent: *

        ROBOTS;

        $shortCodes = $this->crawlingHelper->listCrawlableShortCodes();
        foreach ($shortCodes as $shortCode) {
            yield sprintf('Allow: /%s%s', $shortCode, PHP_EOL);
        }

        yield 'Disallow: /';
    }
}
