<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Endroid\QrCode\Builder\Builder;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\Response\QrCodeResponse;
use Shlinkio\Shlink\Core\Action\Model\QrCodeParams;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Options\QrCodeOptions;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;

class QrCodeAction implements MiddlewareInterface
{
    public function __construct(
        private ShortUrlResolverInterface $urlResolver,
        private ShortUrlStringifierInterface $stringifier,
        private LoggerInterface $logger,
        private QrCodeOptions $defaultOptions,
    ) {
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

        $params = QrCodeParams::fromRequest($request, $this->defaultOptions);
        $qrCodeBuilder = Builder::create()
            ->data($this->stringifier->stringify($shortUrl))
            ->size($params->size())
            ->margin($params->margin())
            ->writer($params->writer())
            ->errorCorrectionLevel($params->errorCorrectionLevel())
            ->roundBlockSizeMode($params->roundBlockSizeMode());

        return new QrCodeResponse($qrCodeBuilder->build());
    }
}
