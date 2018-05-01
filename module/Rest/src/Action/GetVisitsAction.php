<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Service\VisitsTrackerInterface;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\JsonResponse;
use Zend\I18n\Translator\TranslatorInterface;

class GetVisitsAction extends AbstractRestAction
{
    protected const ROUTE_PATH = '/short-codes/{shortCode}/visits';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    /**
     * @var VisitsTrackerInterface
     */
    private $visitsTracker;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        VisitsTrackerInterface $visitsTracker,
        TranslatorInterface $translator,
        LoggerInterface $logger = null
    ) {
        parent::__construct($logger);
        $this->visitsTracker = $visitsTracker;
        $this->translator = $translator;
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \InvalidArgumentException
     */
    public function handle(Request $request): Response
    {
        $shortCode = $request->getAttribute('shortCode');
        $startDate = $this->getDateQueryParam($request, 'startDate');
        $endDate = $this->getDateQueryParam($request, 'endDate');

        try {
            $visits = $this->visitsTracker->info($shortCode, new DateRange($startDate, $endDate));

            return new JsonResponse([
                'visits' => [
                    'data' => $visits,
                ],
            ]);
        } catch (InvalidArgumentException $e) {
            $this->logger->warning('Provided nonexistent shortcode' . PHP_EOL . $e);
            return new JsonResponse([
                'error' => RestUtils::getRestErrorCodeFromException($e),
                'message' => sprintf(
                    $this->translator->translate('Provided short code %s does not exist'),
                    $shortCode
                ),
            ], self::STATUS_NOT_FOUND);
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error while parsing short code' . PHP_EOL . $e);
            return new JsonResponse([
                'error' => RestUtils::UNKNOWN_ERROR,
                'message' => $this->translator->translate('Unexpected error occurred'),
            ], self::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param Request $request
     * @param string $key
     * @return \DateTime|null
     */
    private function getDateQueryParam(Request $request, string $key)
    {
        $query = $request->getQueryParams();
        if (! isset($query[$key]) || empty($query[$key])) {
            return null;
        }

        return new \DateTime($query[$key]);
    }
}
