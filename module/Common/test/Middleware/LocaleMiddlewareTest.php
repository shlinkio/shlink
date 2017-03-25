<?php
namespace ShlinkioTest\Shlink\Common\Middleware;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Middleware\LocaleMiddleware;
use ShlinkioTest\Shlink\Common\Util\TestUtils;
use Zend\Diactoros\ServerRequestFactory;
use Zend\I18n\Translator\Translator;

class LocaleMiddlewareTest extends TestCase
{
    /**
     * @var LocaleMiddleware
     */
    protected $middleware;
    /**
     * @var Translator
     */
    protected $translator;

    public function setUp()
    {
        $this->translator = Translator::factory(['locale' => 'ru']);
        $this->middleware = new LocaleMiddleware($this->translator);
    }

    /**
     * @test
     */
    public function whenNoHeaderIsPresentLocaleIsNotChanged()
    {
        $this->assertEquals('ru', $this->translator->getLocale());
        $this->middleware->process(ServerRequestFactory::fromGlobals(), TestUtils::createDelegateMock()->reveal());
        $this->assertEquals('ru', $this->translator->getLocale());
    }

    /**
     * @test
     */
    public function whenTheHeaderIsPresentLocaleIsChanged()
    {
        $this->assertEquals('ru', $this->translator->getLocale());
        $request = ServerRequestFactory::fromGlobals()->withHeader('Accept-Language', 'es');
        $this->middleware->process($request, TestUtils::createDelegateMock()->reveal());
        $this->assertEquals('es', $this->translator->getLocale());
    }

    /**
     * @test
     */
    public function localeGetsNormalized()
    {
        $delegate = TestUtils::createDelegateMock();

        $this->assertEquals('ru', $this->translator->getLocale());

        $request = ServerRequestFactory::fromGlobals()->withHeader('Accept-Language', 'es_ES');
        $this->middleware->process($request, $delegate->reveal());
        $this->assertEquals('es', $this->translator->getLocale());

        $request = ServerRequestFactory::fromGlobals()->withHeader('Accept-Language', 'en-US');
        $this->middleware->process($request, $delegate->reveal());
        $this->assertEquals('en', $this->translator->getLocale());
    }
}
