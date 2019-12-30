<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use Zend\Diactoros\Response\JsonResponse;

use function Functional\reduce_left;
use function Shlinkio\Shlink\Common\json_decode;
use function strpos;

/** @deprecated */
class BackwardsCompatibleProblemDetailsMiddleware implements MiddlewareInterface
{
    private const BACKWARDS_COMPATIBLE_FIELDS = [
        'error' => 'type',
        'message' => 'detail',
    ];

    private int $jsonFlags;

    public function __construct(int $jsonFlags)
    {
        $this->jsonFlags = $jsonFlags;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $resp = $handler->handle($request);
        if ($resp->getHeaderLine('Content-type') !== 'application/problem+json' || ! $this->isVersionOne($request)) {
            return $resp;
        }

        try {
            $body = (string) $resp->getBody();
            $payload = $this->makePayloadBackwardsCompatible(json_decode($body));
        } catch (Throwable $e) {
            return $resp;
        }

        return new JsonResponse($payload, $resp->getStatusCode(), $resp->getHeaders(), $this->jsonFlags);
    }

    private function isVersionOne(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();
        return strpos($path, '/v') === false || strpos($path, '/v1') === 0;
    }

    private function makePayloadBackwardsCompatible(array $payload): array
    {
        return reduce_left(self::BACKWARDS_COMPATIBLE_FIELDS, function (string $newKey, string $oldKey, $c, $acc) {
            $acc[$oldKey] = $acc[$newKey];
            return $acc;
        }, $payload);
    }
}
