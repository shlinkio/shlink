<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\RedirectRule;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\RedirectRule\Model\RedirectRulesData;
use Shlinkio\Shlink\Core\RedirectRule\ShortUrlRedirectRuleServiceInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

class SetRedirectRulesAction extends AbstractRestAction
{
    protected const string ROUTE_PATH = '/short-urls/{shortCode}/redirect-rules';
    protected const array ROUTE_ALLOWED_METHODS = [self::METHOD_POST, self::METHOD_PATCH];

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
        $data = RedirectRulesData::fromRawData((array) $request->getParsedBody());

        $result = $this->ruleService->setRulesForShortUrl($shortUrl, $data);

        return new JsonResponse([
            'defaultLongUrl' => $shortUrl->getLongUrl(),
            'redirectRules' => $result,
        ]);
    }
}
