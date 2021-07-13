<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelInterface;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelQuartile;
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

use function strtoupper;
use function trim;

class QrCodeAction implements MiddlewareInterface
{
    private const DEFAULT_SIZE = 300;
    private const MIN_SIZE = 50;
    private const MAX_SIZE = 1000;

    private LoggerInterface $logger;

    public function __construct(
        private ShortUrlResolverInterface $urlResolver,
        private ShortUrlStringifierInterface $stringifier,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
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
        $qrCodeBuilder = Builder::create()
            ->data($this->stringifier->stringify($shortUrl))
            ->size($this->resolveSize($request, $query))
            ->margin($this->resolveMargin($query))
            ->errorCorrectionLevel($this->resolveErrorCorrection($query));

        $format = $query['format'] ?? 'png';
        if ($format === 'svg') {
            $qrCodeBuilder->writer(new SvgWriter());
        }

        return new QrCodeResponse($qrCodeBuilder->build());
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
        $margin = $query['margin'] ?? null;
        if ($margin === null) {
            return 0;
        }

        $intMargin = (int) $margin;
        if ($margin !== (string) $intMargin) {
            return 0;
        }

        return $intMargin < 0 ? 0 : $intMargin;
    }

    private function resolveErrorCorrection(array $query): ErrorCorrectionLevelInterface
    {
        $errorCorrectionLevel = strtoupper(trim($query['errorCorrection'] ?? ''));
        return match ($errorCorrectionLevel) {
            'H' => new ErrorCorrectionLevelHigh(),
            'Q' => new ErrorCorrectionLevelQuartile(),
            'M' => new ErrorCorrectionLevelMedium(),
            default => new ErrorCorrectionLevelLow(), // 'L'
        };
    }
}
