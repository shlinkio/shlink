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

class BackwardsCompatibleProblemDetailsMiddleware implements MiddlewareInterface
{
    private const BACKWARDS_COMPATIBLE_FIELDS = [
        'error' => 'type',
        'message' => 'detail',
    ];

    /** @var array */
    private $defaultTypeFallbacks;
    /** @var int */
    private $jsonFlags;

    public function __construct(array $defaultTypeFallbacks, int $jsonFlags)
    {
        $this->defaultTypeFallbacks = $defaultTypeFallbacks;
        $this->jsonFlags = $jsonFlags;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $resp = $handler->handle($request);

        if ($resp->getHeaderLine('Content-type') !== 'application/problem+json') {
            return $resp;
        }

        try {
            $body = (string) $resp->getBody();
            $payload = json_decode($body);
        } catch (Throwable $e) {
            return $resp;
        }

        $payload = $this->mapStandardErrorTypes($payload, $resp->getStatusCode());

        if ($this->isVersionOne($request)) {
            $payload = $this->makePayloadBackwardsCompatible($payload);
        }

        return new JsonResponse($payload, $resp->getStatusCode(), $resp->getHeaders(), $this->jsonFlags);
    }

    private function mapStandardErrorTypes(array $payload, int $respStatusCode): array
    {
        $type = $payload['type'] ?? '';
        if (strpos($type, 'https://httpstatus.es') === 0) {
            $payload['type'] = $this->defaultTypeFallbacks[$respStatusCode] ?? $type;
        }

        return $payload;
    }

    /** @deprecated When Shlink 2 is released, do not chekc the version */
    private function isVersionOne(ServerRequestInterface $request): bool
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        return strpos($path, '/v') === false || strpos($path, '/v1') === 0;
    }

    /** @deprecated When Shlink v2 is released, do not map old fields */
    private function makePayloadBackwardsCompatible(array $payload): array
    {
        return reduce_left(self::BACKWARDS_COMPATIBLE_FIELDS, function (string $newKey, string $oldKey, $c, $acc) {
            $acc[$oldKey] = $acc[$newKey];
            return $acc;
        }, $payload);
    }
}
