<?php
namespace ShlinkioTest\Shlink\Common\Middleware;

use PHPUnit_Framework_TestCase as TestCase;
use Shlinkio\Shlink\Common\Middleware\LocaleMiddleware;
use Zend\Diactoros\Response;
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
        $this->middleware->__invoke(ServerRequestFactory::fromGlobals(), new Response(), function ($req, $resp) {
            return $resp;
        });
        $this->assertEquals('ru', $this->translator->getLocale());
    }

    /**
     * @test
     */
    public function whenTheHeaderIsPresentLocaleIsChanged()
    {
        $this->assertEquals('ru', $this->translator->getLocale());
        $request = ServerRequestFactory::fromGlobals()->withHeader('Accept-Language', 'es');
        $this->middleware->__invoke($request, new Response(), function ($req, $resp) {
            return $resp;
        });
        $this->assertEquals('es', $this->translator->getLocale());
    }

    /**
     * @test
     */
    public function localeGetsNormalized()
    {
        $this->assertEquals('ru', $this->translator->getLocale());

        $request = ServerRequestFactory::fromGlobals()->withHeader('Accept-Language', 'es_ES');
        $this->middleware->__invoke($request, new Response(), function ($req, $resp) {
            return $resp;
        });
        $this->assertEquals('es', $this->translator->getLocale());

        $request = ServerRequestFactory::fromGlobals()->withHeader('Accept-Language', 'en-US');
        $this->middleware->__invoke($request, new Response(), function ($req, $resp) {
            return $resp;
        });
        $this->assertEquals('en', $this->translator->getLocale());
    }
}
