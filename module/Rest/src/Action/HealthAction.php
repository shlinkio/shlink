<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action;

use Doctrine\ORM\EntityManagerInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Throwable;

class HealthAction extends AbstractRestAction
{
    private const HEALTH_CONTENT_TYPE = 'application/health+json';
    private const STATUS_PASS = 'pass';
    private const STATUS_FAIL = 'fail';

    protected const ROUTE_PATH = '/health';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    public function __construct(private EntityManagerInterface $em, private AppOptions $options)
    {
    }

    /**
     * Handles a request and produces a response.
     *
     * May call other collaborating code to generate the response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $connection = $this->em->getConnection();
            $connection->executeQuery($connection->getDatabasePlatform()->getDummySelectSQL());
            $connected = true;
        } catch (Throwable) {
            $connected = false;
        }

        $statusCode = $connected ? self::STATUS_OK : self::STATUS_SERVICE_UNAVAILABLE;
        return new JsonResponse([
            'status' => $connected ? self::STATUS_PASS : self::STATUS_FAIL,
            'version' => $this->options->getVersion(),
            'links' => [
                'about' => 'https://shlink.io',
                'project' => 'https://github.com/shlinkio/shlink',
            ],
        ], $statusCode, ['Content-type' => self::HEALTH_CONTENT_TYPE]);
    }
}
