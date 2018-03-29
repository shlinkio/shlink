<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Psr\Http\Message\ResponseInterface;
use Shlinkio\Shlink\Common\Response\PixelResponse;

class PixelAction extends AbstractTrackingAction
{
    protected function createResp(string $longUrl): ResponseInterface
    {
        return new PixelResponse();
    }
}
