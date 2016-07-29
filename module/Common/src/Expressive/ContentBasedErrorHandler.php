<?php
namespace Shlinkio\Shlink\Common\Expressive;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Exception\InvalidServiceException;

class ContentBasedErrorHandler extends AbstractPluginManager implements ErrorHandlerInterface
{
    const DEFAULT_CONTENT = 'text/html';

    public function validate($instance)
    {
        if (is_callable($instance)) {
            return;
        }

        throw new InvalidServiceException(sprintf(
            'Only callables are valid plugins for "%s". "%s" provided',
            __CLASS__,
            is_object($instance) ? get_class($instance) : gettype($instance)
        ));
    }

    /**
     * Final handler for an application.
     *
     * @param Request $request
     * @param Response $response
     * @param null|mixed $err
     * @return Response
     */
    public function __invoke(Request $request, Response $response, $err = null)
    {
        // Try to get an error handler for provided request accepted type
        $errorHandler = $this->resolveErrorHandlerFromAcceptHeader($request);
        return $errorHandler($request, $response, $err);
    }

    /**
     * Tries to resolve
     *
     * @param Request $request
     * @return callable
     */
    protected function resolveErrorHandlerFromAcceptHeader(Request $request)
    {
        // Try to find an error handler for one of the accepted content types
        $accepts = $request->hasHeader('Accept') ? $request->getHeaderLine('Accept') : self::DEFAULT_CONTENT;
        $accepts = explode(',', $accepts);
        foreach ($accepts as $accept) {
            if (! $this->has($accept)) {
                continue;
            }

            return $this->get($accept);
        }

        // If it wasn't possible to find an error handler for accepted content type, use default one if registered
        if ($this->has(self::DEFAULT_CONTENT)) {
            return $this->get(self::DEFAULT_CONTENT);
        }

        // It wasn't possible to find an error handler
        throw new InvalidArgumentException(sprintf(
            'It wasn\'t possible to find an error handler for ["%s"] content types. '
            . 'Make sure you have registered at least the default "%s" content type',
            implode('", "', $accepts),
            self::DEFAULT_CONTENT
        ));
    }
}
