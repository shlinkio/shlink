<?php
namespace Shlinkio\Shlink\Rest\Action;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Core\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Core\Service\VisitsTracker;
use Shlinkio\Shlink\Core\Service\VisitsTrackerInterface;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\JsonResponse;

class GetVisitsMiddleware extends AbstractRestMiddleware
{
    /**
     * @var VisitsTrackerInterface
     */
    private $visitsTracker;

    /**
     * GetVisitsMiddleware constructor.
     * @param VisitsTrackerInterface|VisitsTracker $visitsTracker
     *
     * @Inject({VisitsTracker::class})
     */
    public function __construct(VisitsTrackerInterface $visitsTracker)
    {
        $this->visitsTracker = $visitsTracker;
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

        try {
            $visits = $this->visitsTracker->info($shortCode);

            return new JsonResponse([
                'visits' => [
                    'data' => $visits,
//                    'pagination' => [],
                ]
            ]);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => RestUtils::getRestErrorCodeFromException($e),
                'message' => sprintf('Provided short code "%s" is invalid', $shortCode),
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => RestUtils::UNKNOWN_ERROR,
                'message' => 'Unexpected error occured',
            ], 500);
        }
    }
}
