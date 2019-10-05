<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Service\ShortUrlService;
use Shlinkio\Shlink\Rest\Action\ShortUrl\ListShortUrlsAction;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;
use Zend\Paginator\Adapter\ArrayAdapter;
use Zend\Paginator\Paginator;

class ListShortUrlsActionTest extends TestCase
{
    /** @var ListShortUrlsAction */
    private $action;
    /** @var ObjectProphecy */
    private $service;
    /** @var ObjectProphecy */
    private $logger;

    public function setUp(): void
    {
        $this->service = $this->prophesize(ShortUrlService::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        $this->action = new ListShortUrlsAction($this->service->reveal(), [
            'hostname' => 'doma.in',
            'schema' => 'https',
        ], $this->logger->reveal());
    }

    /**
     * @test
     * @dataProvider provideFilteringData
     */
    public function properListReturnsSuccessResponse(
        array $query,
        int $expectedPage,
        ?string $expectedSearchTerm,
        array $expectedTags,
        ?string $expectedOrderBy
    ): void {
        $listShortUrls = $this->service->listShortUrls(
            $expectedPage,
            $expectedSearchTerm,
            $expectedTags,
            $expectedOrderBy
        )->willReturn(new Paginator(new ArrayAdapter()));

        /** @var JsonResponse $response */
        $response = $this->action->handle((new ServerRequest())->withQueryParams($query));
        $payload = $response->getPayload();

        $this->assertArrayHasKey('shortUrls', $payload);
        $this->assertArrayHasKey('data', $payload['shortUrls']);
        $this->assertEquals([], $payload['shortUrls']['data']);
        $this->assertEquals(200, $response->getStatusCode());
        $listShortUrls->shouldHaveBeenCalledOnce();
    }

    public function provideFilteringData(): iterable
    {
        yield [[], 1, null, [], null];
        yield [['page' => 10], 10, null, [], null];
        yield [['page' => null], 1, null, [], null];
        yield [['page' => '8'], 8, null, [], null];
        yield [['searchTerm' => $searchTerm = 'foo'], 1, $searchTerm, [], null];
        yield [['tags' => $tags = ['foo','bar']], 1, null, $tags, null];
        yield [['orderBy' => $orderBy = 'something'], 1, null, [], $orderBy];
        yield [[
            'page' => '2',
            'orderBy' => $orderBy = 'something',
            'tags' => $tags = ['one', 'two'],
        ], 2, null, $tags, $orderBy];
    }

    /** @test */
    public function anExceptionReturnsErrorResponse(): void
    {
        $page = 3;
        $e = new Exception();

        $this->service->listShortUrls($page, null, [], null)->willThrow($e)
                                                            ->shouldBeCalledOnce();
        $logError = $this->logger->error(
            'Unexpected error while listing short URLs. {e}',
            ['e' => $e]
        )->will(function () {
        });

        $response = $this->action->handle((new ServerRequest())->withQueryParams([
            'page' => $page,
        ]));

        $this->assertEquals(500, $response->getStatusCode());
        $logError->shouldHaveBeenCalledOnce();
    }
}
