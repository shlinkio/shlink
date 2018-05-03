<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action;

use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Core\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Model\CreateShortCodeData;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Rest\Action\ShortCode\AbstractCreateShortCodeAction;
use Zend\Diactoros\Uri;

class CreateShortCodeAction extends AbstractCreateShortCodeAction
{
    protected const ROUTE_PATH = '/short-codes';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_POST];

    /**
     * @param Request $request
     * @return CreateShortCodeData
     * @throws ValidationException
     * @throws InvalidArgumentException
     * @throws \InvalidArgumentException
     */
    protected function buildUrlToShortCodeData(Request $request): CreateShortCodeData
    {
        $postData = (array) $request->getParsedBody();
        if (! isset($postData['longUrl'])) {
            throw new InvalidArgumentException('A URL was not provided');
        }

        return new CreateShortCodeData(
            new Uri($postData['longUrl']),
            (array) ($postData['tags'] ?? []),
            ShortUrlMeta::createFromParams(
                $this->getOptionalDate($postData, 'validSince'),
                $this->getOptionalDate($postData, 'validUntil'),
                $postData['customSlug'] ?? null,
                isset($postData['maxVisits']) ? (int) $postData['maxVisits'] : null
            )
        );
    }

    private function getOptionalDate(array $postData, string $fieldName)
    {
        return isset($postData[$fieldName]) ? new \DateTime($postData[$fieldName]) : null;
    }
}
