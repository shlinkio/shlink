<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\ErrorHandler;

use Acelaya\ExpressiveErrorHandler\ErrorHandler\ErrorResponseGeneratorInterface;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\JsonResponse;

class JsonErrorResponseGenerator implements ErrorResponseGeneratorInterface, StatusCodeInterface
{
    /**
     * Final handler for an application.
     *
     * @param \Throwable|\Exception $e
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws \InvalidArgumentException
     */
    public function __invoke(?\Throwable $e, Request $request, Response $response)
    {
        $status = $response->getStatusCode();
        $responsePhrase = $status < 400 ? 'Internal Server Error' : $response->getReasonPhrase();
        $status = $status < 400 ? self::STATUS_INTERNAL_SERVER_ERROR : $status;

        return new JsonResponse([
            'error' => $this->responsePhraseToCode($responsePhrase),
            'message' => $responsePhrase,
        ], $status);
    }

    protected function responsePhraseToCode(string $responsePhrase): string
    {
        return strtoupper(str_replace(' ', '_', $responsePhrase));
    }
}
