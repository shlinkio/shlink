<?php
namespace Shlinkio\Shlink\Rest\Expressive;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Shlinkio\Shlink\Common\Expressive\ErrorHandlerInterface;
use Zend\Diactoros\Response\JsonResponse;

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
        $status = $response->getStatusCode();
        $responsePhrase = $status < 400 ? 'Internal Server Error' : $response->getReasonPhrase();
        $status = $status < 400 ? 500 : $status;

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
