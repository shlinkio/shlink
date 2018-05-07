<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortCode;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Service\ShortUrlServiceInterface;
use Shlinkio\Shlink\Rest\Action\ShortCode\EditShortCodeAction;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequestFactory;
use Zend\I18n\Translator\Translator;

class EditShortCodeActionTest extends TestCase
{
    /**
     * @var EditShortCodeAction
     */
    private $action;
    /**
     * @var ObjectProphecy
     */
    private $shortUrlService;

    public function setUp()
    {
        $this->shortUrlService = $this->prophesize(ShortUrlServiceInterface::class);
        $this->action = new EditShortCodeAction($this->shortUrlService->reveal(), Translator::factory([]));
    }

    /**
     * @test
     */
    public function invalidDataReturnsError()
    {
        $request = ServerRequestFactory::fromGlobals()->withParsedBody([
            'maxVisits' => 'invalid',
        ]);

        /** @var JsonResponse $resp */
        $resp = $this->action->handle($request);
        $payload = $resp->getPayload();

        $this->assertEquals(400, $resp->getStatusCode());
        $this->assertEquals(RestUtils::INVALID_ARGUMENT_ERROR, $payload['error']);
        $this->assertEquals('Provided data is invalid.', $payload['message']);
    }

    /**
     * @test
     */
    public function incorrectShortCodeReturnsError()
    {
        $request = ServerRequestFactory::fromGlobals()->withAttribute('shortCode', 'abc123')
                                                      ->withParsedBody([
                                                          'maxVisits' => 5,
                                                      ]);
        $updateMeta = $this->shortUrlService->updateMetadataByShortCode(Argument::cetera())->willThrow(
            InvalidShortCodeException::class
        );

        /** @var JsonResponse $resp */
        $resp = $this->action->handle($request);
        $payload = $resp->getPayload();

        $this->assertEquals(404, $resp->getStatusCode());
        $this->assertEquals(RestUtils::INVALID_SHORTCODE_ERROR, $payload['error']);
        $this->assertEquals('No URL found for short code "abc123"', $payload['message']);
        $updateMeta->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function correctShortCodeReturnsSuccess()
    {
        $request = ServerRequestFactory::fromGlobals()->withAttribute('shortCode', 'abc123')
                                                      ->withParsedBody([
                                                          'maxVisits' => 5,
                                                      ]);
        $updateMeta = $this->shortUrlService->updateMetadataByShortCode(Argument::cetera())->willReturn(new ShortUrl());

        $resp = $this->action->handle($request);

        $this->assertEquals(204, $resp->getStatusCode());
        $updateMeta->shouldHaveBeenCalled();
    }
}
