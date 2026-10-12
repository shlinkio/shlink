<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action;

use Cake\Chronos\Chronos;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Common\Mercure\JwtProviderInterface;
use Shlinkio\Shlink\Common\Mercure\MercureOptions;
use Shlinkio\Shlink\Rest\Exception\MercureException;

use function sprintf;

class MercureInfoAction extends AbstractRestAction
{
    protected const string ROUTE_PATH = '/mercure-info';
    protected const array ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    public function __construct(
        private readonly JwtProviderInterface $jwtProvider,
        private readonly MercureOptions $mercureOptions,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $hubUrl = $this->mercureOptions->publicHubUrl;
        if ($hubUrl === null) {
            throw MercureException::mercureNotConfigured();
        }

        // Make subscription tokens expire in 1 day
        $expiresAt = Chronos::now()->addDays(1);
        $jwt = $this->jwtProvider->buildSubscriptionToken($expiresAt);

        return new JsonResponse([
            'mercureHubUrl' => sprintf('%s/.well-known/mercure', $hubUrl),
            'token' => $jwt,
            'jwtExpiration' => $expiresAt->toAtomString(),
            'version' => $this->mercureOptions->version->value,
        ]);
    }
}
