<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action;

use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Throwable;
use Zend\Diactoros\Response\JsonResponse;

class HealthAction extends AbstractRestAction
{
    private const HEALTH_CONTENT_TYPE = 'application/health+json';
    private const PASS_STATUS = 'pass';
    private const FAIL_STATUS = 'fail';

    protected const ROUTE_PATH = '/health';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    /** @var AppOptions */
    private $options;
    /** @var Connection */
    private $conn;

    public function __construct(Connection $conn, AppOptions $options, ?LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->conn = $conn;
        $this->options = $options;
    }

    /**
     * Handles a request and produces a response.
     *
     * May call other collaborating code to generate the response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $connected = $this->conn->ping();
        } catch (Throwable $e) {
            $connected = false;
        }

        $statusCode = $connected ? self::STATUS_OK : self::STATUS_SERVICE_UNAVAILABLE;
        return new JsonResponse([
            'status' => $connected ? self::PASS_STATUS : self::FAIL_STATUS,
            'version' => $this->options->getVersion(),
            'links' => [
                'about' => 'https://shlink.io',
                'project' => 'https://github.com/shlinkio/shlink',
            ],
        ], $statusCode, ['Content-type' => self::HEALTH_CONTENT_TYPE]);
    }
}
