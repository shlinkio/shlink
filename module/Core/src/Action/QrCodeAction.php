<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shlinkio\Shlink\Common\Response\QrCodeResponse;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;

class QrCodeAction implements MiddlewareInterface
{
    private const DEFAULT_SIZE = 300;
    private const MIN_SIZE = 50;
    private const MAX_SIZE = 1000;

    private ShortUrlResolverInterface $urlResolver;
    private ShortUrlStringifierInterface $stringifier;
    private LoggerInterface $logger;

    public function __construct(
        ShortUrlResolverInterface $urlResolver,
        ShortUrlStringifierInterface $stringifier,
        ?LoggerInterface $logger = null
    ) {
        $this->urlResolver = $urlResolver;
        $this->logger = $logger ?? new NullLogger();
        $this->stringifier = $stringifier;
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $identifier = ShortUrlIdentifier::fromRedirectRequest($request);

        try {
            $shortUrl = $this->urlResolver->resolveEnabledShortUrl($identifier);
        } catch (ShortUrlNotFoundException $e) {
            $this->logger->warning('An error occurred while creating QR code. {e}', ['e' => $e]);
            return $handler->handle($request);
        }

        $query = $request->getQueryParams();
        $qrCode = Builder::create()
            ->data($this->stringifier->stringify($shortUrl))
            ->size($this->resolveSize($request, $query))
            ->margin($this->resolveMargin($query));

        $format = $query['format'] ?? 'png';
        if ($format === 'svg') {
            $qrCode->writer(new SvgWriter());
        }

        return new QrCodeResponse($qrCode->build());
    }

    private function resolveSize(Request $request, array $query): int
    {
        // Size attribute is deprecated. After v3.0.0, always use the query param instead
        $size = (int) $request->getAttribute('size', $query['size'] ?? self::DEFAULT_SIZE);
        if ($size < self::MIN_SIZE) {
            return self::MIN_SIZE;
        }

        return $size > self::MAX_SIZE ? self::MAX_SIZE : $size;
    }

    private function resolveMargin(array $query): int
    {
        if (! isset($query['margin'])) {
            return 0;
        }

        $margin = $query['margin'];
        $intMargin = (int) $margin;
        if ($margin !== (string) $intMargin) {
            return 0;
        }

        return $intMargin < 0 ? 0 : $intMargin;
    }
}
