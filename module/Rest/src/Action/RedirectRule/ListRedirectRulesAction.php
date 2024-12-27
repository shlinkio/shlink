<?php

namespace Shlinkio\Shlink\Rest\Action\RedirectRule;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\RedirectRule\ShortUrlRedirectRuleServiceInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

class ListRedirectRulesAction extends AbstractRestAction
{
    protected const string ROUTE_PATH = '/short-urls/{shortCode}/redirect-rules';
    protected const array ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    public function __construct(
        private readonly ShortUrlResolverInterface $urlResolver,
        private readonly ShortUrlRedirectRuleServiceInterface $ruleService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $shortUrl = $this->urlResolver->resolveShortUrl(
            ShortUrlIdentifier::fromApiRequest($request),
            AuthenticationMiddleware::apiKeyFromRequest($request),
        );
        $rules = $this->ruleService->rulesForShortUrl($shortUrl);

        return new JsonResponse([
            'defaultLongUrl' => $shortUrl->getLongUrl(),
            'redirectRules' => $rules,
        ]);
    }
}
