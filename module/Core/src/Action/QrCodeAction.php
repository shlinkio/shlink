<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\Response\QrCodeResponse;
use Shlinkio\Shlink\Core\Action\Model\QrCodeParams;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Options\QrCodeOptions;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolverInterface;

readonly class QrCodeAction implements MiddlewareInterface
{
    public function __construct(
        private ShortUrlResolverInterface $urlResolver,
        private ShortUrlStringifierInterface $stringifier,
        private LoggerInterface $logger,
        private QrCodeOptions $options,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $identifier = ShortUrlIdentifier::fromRedirectRequest($request);

        try {
            $shortUrl = $this->options->enabledForDisabledShortUrls
                ? $this->urlResolver->resolvePublicShortUrl($identifier)
                : $this->urlResolver->resolveEnabledShortUrl($identifier);
        } catch (ShortUrlNotFoundException $e) {
            $this->logger->warning('An error occurred while creating QR code. {e}', ['e' => $e]);
            return $handler->handle($request);
        }

        $params = QrCodeParams::fromRequest($request, $this->options);
        $qrCodeBuilder = new Builder(
            writer: $params->writer,
            data: $this->stringifier->stringify($shortUrl),
            errorCorrectionLevel: $params->errorCorrectionLevel,
            size: $params->size,
            margin: $params->margin,
            roundBlockSizeMode: $params->roundBlockSizeMode,
            foregroundColor: $params->color,
            backgroundColor: $params->bgColor,
        );

        return new QrCodeResponse($this->buildQrCode($qrCodeBuilder, $params));
    }

    private function buildQrCode(Builder $qrCodeBuilder, QrCodeParams $params): ResultInterface
    {
        $logoUrl = $this->options->logoUrl;
        if ($logoUrl === null) {
            return $qrCodeBuilder->build();
        }

        return $qrCodeBuilder->build(
            logoPath: $logoUrl,
            logoResizeToHeight: (int) ($params->size / 4),
        );
    }
}
