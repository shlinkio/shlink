<?php

namespace Shlinkio\Shlink\Core\RedirectRule;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

readonly class ShortUrlRedirectionResolver implements ShortUrlRedirectionResolverInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function resolveLongUrl(ShortUrl $shortUrl, ServerRequestInterface $request): string
    {
        // TODO Resolve rules and check if any of them matches

        $device = DeviceType::matchFromUserAgent($request->getHeaderLine('User-Agent'));
        return $shortUrl->longUrlForDevice($device);
    }
}
