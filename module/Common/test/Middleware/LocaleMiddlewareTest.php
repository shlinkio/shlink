<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Middleware;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Middleware\LocaleMiddleware;
use ShlinkioTest\Shlink\Common\Util\TestUtils;
use Zend\Diactoros\ServerRequest;
use Zend\I18n\Translator\Translator;

class LocaleMiddlewareTest extends TestCase
{
    /** @var LocaleMiddleware */
    private $middleware;
    /** @var Translator */
    private $translator;

    public function setUp(): void
    {
        $this->translator = Translator::factory(['locale' => 'ru']);
        $this->middleware = new LocaleMiddleware($this->translator);
    }

    /** @test */
    public function whenNoHeaderIsPresentLocaleIsNotChanged(): void
    {
        $this->assertEquals('ru', $this->translator->getLocale());
        $this->middleware->process(new ServerRequest(), TestUtils::createReqHandlerMock()->reveal());
        $this->assertEquals('ru', $this->translator->getLocale());
    }

    /** @test */
    public function whenTheHeaderIsPresentLocaleIsChanged(): void
    {
        $this->assertEquals('ru', $this->translator->getLocale());
        $request = (new ServerRequest())->withHeader('Accept-Language', 'es');
        $this->middleware->process($request, TestUtils::createReqHandlerMock()->reveal());
        $this->assertEquals('es', $this->translator->getLocale());
    }

    /**
     * @test
     * @dataProvider provideLanguages
     */
    public function localeGetsNormalized(string $lang, string $expected): void
    {
        $handler = TestUtils::createReqHandlerMock();

        $this->assertEquals('ru', $this->translator->getLocale());

        $request = (new ServerRequest())->withHeader('Accept-Language', $lang);
        $this->middleware->process($request, $handler->reveal());
        $this->assertEquals($expected, $this->translator->getLocale());
    }

    public function provideLanguages(): iterable
    {
        yield 'language only' => ['ru', 'ru'];
        yield 'country and language with underscore' => ['es_ES', 'es'];
        yield 'country and language with dash' => ['en-US', 'en'];
    }
}
