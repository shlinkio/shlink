<?php
namespace ShlinkioTest\Shlink\Common\Template\Extension;

use League\Plates\Engine;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Shlinkio\Shlink\Common\Template\Extension\TranslatorExtension;
use Zend\I18n\Translator\Translator;

class TranslatorExtensionTest extends TestCase
{
    /**
     * @var TranslatorExtension
     */
    protected $extension;

    public function setUp()
    {
        $this->extension = new TranslatorExtension($this->prophesize(Translator::class)->reveal());
    }

    /**
     * @test
     */
    public function properFunctionsAreReturned()
    {
        $engine = $this->prophesize(Engine::class);

        $engine->registerFunction('translate', Argument::type('callable'))->shouldBeCalledTimes(1);
        $engine->registerFunction('translate_plural', Argument::type('callable'))->shouldBeCalledTimes(1);

        $funcs = $this->extension->register($engine->reveal());
    }
}
