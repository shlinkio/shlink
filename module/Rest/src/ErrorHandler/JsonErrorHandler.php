<?php
namespace Shlinkio\Shlink\Rest\ErrorHandler;

use Acelaya\ExpressiveErrorHandler\ErrorHandler\ErrorHandlerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Expressive\Router\RouteResult;

class JsonErrorHandler implements ErrorHandlerInterface
{
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
        $hasRoute = $request->getAttribute(RouteResult::class) !== null;
        $isNotFound = ! $hasRoute && ! isset($err);
        if ($isNotFound) {
            $responsePhrase = 'Not found';
            $status = 404;
        } else {
            $status = $response->getStatusCode();
            $responsePhrase = $status < 400 ? 'Internal Server Error' : $response->getReasonPhrase();
            $status = $status < 400 ? 500 : $status;
        }

        return new JsonResponse([
            'error' => $this->responsePhraseToCode($responsePhrase),
            'message' => $responsePhrase,
        ], $status);
    }

    /**
     * @param string $responsePhrase
     * @return string
     */
    protected function responsePhraseToCode($responsePhrase)
    {
        return strtoupper(str_replace(' ', '_', $responsePhrase));
    }
}
