<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action;

use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function array_merge;

abstract class AbstractRestAction implements RequestHandlerInterface, RequestMethodInterface, StatusCodeInterface
{
    protected const ROUTE_PATH = '';
    protected const ROUTE_ALLOWED_METHODS = [];

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new NullLogger();
    }

    public static function getRouteDef(array $prevMiddleware = [], array $postMiddleware = []): array
    {
        return [
            'name' => static::class,
            'middleware' => array_merge($prevMiddleware, [static::class], $postMiddleware),
            'path' => static::ROUTE_PATH,
            'allowed_methods' => static::ROUTE_ALLOWED_METHODS,
        ];
    }
}
