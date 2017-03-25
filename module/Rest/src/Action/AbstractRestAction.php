<?php
namespace Shlinkio\Shlink\Rest\Action;

use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Zend\Diactoros\Response\EmptyResponse;

abstract class AbstractRestAction implements MiddlewareInterface, RequestMethodInterface, StatusCodeInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param Request $request
     * @param DelegateInterface $delegate
     *
     * @return Response
     */
    public function process(Request $request, DelegateInterface $delegate)
    {
        if ($request->getMethod() === self::METHOD_OPTIONS) {
            return new EmptyResponse();
        }

        return $this->dispatch($request, $delegate);
    }

    /**
     * @param Request $request
     * @param DelegateInterface $delegate
     * @return null|Response
     */
    abstract protected function dispatch(Request $request, DelegateInterface $delegate);
}
