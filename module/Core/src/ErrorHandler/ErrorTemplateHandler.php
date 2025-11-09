<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ErrorHandler;

use Closure;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Throwable;

use function assert;
use function file_get_contents;
use function is_string;
use function sprintf;
use function str_replace;

class ErrorTemplateHandler
{
    private const string TEMPLATES_BASE_DIR = __DIR__ . '/../../templates';
    public const string ERROR_TEMPLATE = 'error.html';

    private Closure $readFile;

    public function __construct(callable|null $readFile = null)
    {
        $this->readFile = $readFile ? Closure::fromCallable($readFile) : fn (string $file) => file_get_contents($file);
    }

    public function createErrorResponse(Throwable $error, int $statusCode, string|null $customDescription = null): ResponseInterface
    {
        $templateContent = ($this->readFile)(sprintf('%s/%s', self::TEMPLATES_BASE_DIR, self::ERROR_TEMPLATE));
        assert(is_string($templateContent), 'Template file must be readable');

        $title = $this->getTitle($statusCode);
        $message = $this->getMessage($statusCode, $error);
        $description = $customDescription ?? $this->getDescription($statusCode);

        $templateContent = str_replace('{STATUS}', (string) $statusCode, $templateContent);
        $templateContent = str_replace('{TITLE}', $title, $templateContent);
        $templateContent = str_replace('{MESSAGE}', $message, $templateContent);
        $templateContent = str_replace('{DESCRIPTION}', $description, $templateContent);

        return new Response\HtmlResponse($templateContent, $statusCode);
    }

    private function getTitle(int $statusCode): string
    {
        return match ($statusCode) {
            StatusCodeInterface::STATUS_BAD_REQUEST => 'Bad Request',
            StatusCodeInterface::STATUS_UNAUTHORIZED => 'Unauthorized Access',
            StatusCodeInterface::STATUS_FORBIDDEN => 'Access Forbidden',
            StatusCodeInterface::STATUS_NOT_FOUND => 'Oops, This Page Not Found!',
            StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED => 'Method Not Allowed',
            StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR => 'Internal Server Error',
            StatusCodeInterface::STATUS_BAD_GATEWAY => 'Bad Gateway',
            StatusCodeInterface::STATUS_SERVICE_UNAVAILABLE => 'Service Unavailable',
            default => 'An Error Occurred',
        };
    }

    private function getMessage(int $statusCode, Throwable $error): string
    {
        $defaultMessage = match ($statusCode) {
            StatusCodeInterface::STATUS_BAD_REQUEST => 'The request you sent was invalid or malformed.',
            StatusCodeInterface::STATUS_UNAUTHORIZED => 'You need to authenticate to access this resource.',
            StatusCodeInterface::STATUS_FORBIDDEN => 'You don\'t have permission to access this resource.',
            StatusCodeInterface::STATUS_NOT_FOUND => 'The link might be corrupted, or the page may have been removed.',
            StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED => 'The HTTP method used is not allowed for this resource.',
            StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR => 'Something went wrong on our end. Please try again later.',
            StatusCodeInterface::STATUS_BAD_GATEWAY => 'The server received an invalid response from an upstream server.',
            StatusCodeInterface::STATUS_SERVICE_UNAVAILABLE => 'The service is temporarily unavailable. Please try again later.',
            default => 'An unexpected error occurred.',
        };

        return $defaultMessage;
    }

    private function getDescription(int $statusCode): string
    {
        if ($statusCode >= 500) {
            return '<div class="description-box"><strong>What happened?</strong><p>An internal server error occurred. Our team has been notified and is working to fix the issue.</p></div>';
        }

        return match ($statusCode) {
            StatusCodeInterface::STATUS_BAD_REQUEST => '<div class="description-box"><strong>What does this mean?</strong><p>The request you sent contains invalid data or parameters. Please check your input and try again.</p></div>',
            StatusCodeInterface::STATUS_UNAUTHORIZED => '<div class="description-box"><strong>Authentication required</strong><p>You need to provide valid credentials to access this resource.</p></div>',
            StatusCodeInterface::STATUS_FORBIDDEN => '<div class="description-box"><strong>Access denied</strong><p>You don\'t have the necessary permissions to access this resource.</p></div>',
            StatusCodeInterface::STATUS_NOT_FOUND => '',
            StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED => '<div class="description-box"><strong>Method not allowed</strong><p>The HTTP method you used is not supported for this endpoint.</p></div>',
            default => '',
        };
    }
}
