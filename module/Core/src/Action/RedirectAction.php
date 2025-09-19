<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Fig\Http\Message\StatusCodeInterface;
use League\Uri\Uri;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlRedirectionBuilderInterface;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\Util\RedirectResponseHelperInterface;
use Shlinkio\Shlink\Core\Visit\RequestTrackerInterface;
use const Shlinkio\Shlink\SENSITIVE_HOSTS;

class RedirectAction extends AbstractTrackingAction implements StatusCodeInterface
{
    public function __construct(
        ShortUrlResolverInterface $urlResolver,
        RequestTrackerInterface $requestTracker,
        private ShortUrlRedirectionBuilderInterface $redirectionBuilder,
        private RedirectResponseHelperInterface $redirectResponseHelper,
    ) {
        parent::__construct($urlResolver, $requestTracker);
    }

    protected function createSuccessResp(ShortUrl $shortUrl, ServerRequestInterface $request): Response
    {
        $queryParams = (false === $this->isSensitiveHost($shortUrl))
            ? $request->getQueryParams()
            : [];

        $longUrl = $this->redirectionBuilder->buildShortUrlRedirect($shortUrl, $queryParams);

        return $this->redirectResponseHelper->buildRedirectResponse($longUrl);
    }

    /**
     * @param ShortUrl $shortUrl
     * @return bool
     */
    private function isSensitiveHost(ShortUrl $shortUrl): bool
    {
        $uri = Uri::createFromString($shortUrl->getLongUrl());

        return in_array($uri->getHost(), SENSITIVE_HOSTS, true);
    }
}
