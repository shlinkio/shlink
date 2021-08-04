<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action;

use Cake\Chronos\Chronos;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Common\Mercure\JwtProviderInterface;
use Shlinkio\Shlink\Rest\Exception\MercureException;
use Throwable;

use function sprintf;

class MercureInfoAction extends AbstractRestAction
{
    protected const ROUTE_PATH = '/mercure-info';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    public function __construct(private JwtProviderInterface $jwtProvider, private array $mercureConfig)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $hubUrl = $this->mercureConfig['public_hub_url'] ?? null;
        if ($hubUrl === null) {
            throw MercureException::mercureNotConfigured();
        }

        $days = $this->mercureConfig['jwt_days_duration'] ?? 1;
        $expiresAt = Chronos::now()->addDays($days);

        try {
            $jwt = $this->jwtProvider->buildSubscriptionToken($expiresAt);
        } catch (Throwable $e) {
            throw MercureException::mercureNotConfigured($e);
        }

        return new JsonResponse([
            'mercureHubUrl' => sprintf('%s/.well-known/mercure', $hubUrl),
            'token' => $jwt,
            'jwtExpiration' => $expiresAt->toAtomString(),
        ]);
    }
}
