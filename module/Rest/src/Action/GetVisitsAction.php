<?php
namespace Shlinkio\Shlink\Rest\Action;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Service\VisitsTracker;
use Shlinkio\Shlink\Core\Service\VisitsTrackerInterface;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\JsonResponse;
use Zend\I18n\Translator\TranslatorInterface;

class GetVisitsAction extends AbstractRestAction
{
    /**
     * @var VisitsTrackerInterface
     */
    private $visitsTracker;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * GetVisitsAction constructor.
     * @param VisitsTrackerInterface $visitsTracker
     * @param TranslatorInterface $translator
     *
     * @Inject({VisitsTracker::class, "translator"})
     */
    public function __construct(VisitsTrackerInterface $visitsTracker, TranslatorInterface $translator)
    {
        $this->visitsTracker = $visitsTracker;
        $this->translator = $translator;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable|null $out
     * @return null|Response
     */
    public function dispatch(Request $request, Response $response, callable $out = null)
    {
        $shortCode = $request->getAttribute('shortCode');
        $startDate = $this->getDateQueryParam($request, 'startDate');
        $endDate = $this->getDateQueryParam($request, 'endDate');

        try {
            $visits = $this->visitsTracker->info($shortCode, new DateRange($startDate, $endDate));

            return new JsonResponse([
                'visits' => [
                    'data' => $visits,
                ]
            ]);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => RestUtils::getRestErrorCodeFromException($e),
                'message' => sprintf(
                    $this->translator->translate('Provided short code "%s" does not exist'),
                    $shortCode
                ),
            ], 404);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => RestUtils::UNKNOWN_ERROR,
                'message' => $this->translator->translate('Unexpected error occurred'),
            ], 500);
        }
    }

    /**
     * @param Request $request
     * @param $key
     * @return \DateTime|null
     */
    protected function getDateQueryParam(Request $request, $key)
    {
        $query = $request->getQueryParams();
        if (! isset($query[$key]) || empty($query[$key])) {
            return null;
        }

        return new \DateTime($query[$key]);
    }
}
