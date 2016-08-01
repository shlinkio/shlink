<?php
namespace ShlinkioTest\Shlink\Common\Twig\Extension;

use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Twig\Extension\TranslatorExtension;
use Zend\I18n\Translator\Translator;

class TranslatorExtensionTest extends TestCase
{
    /**
     * @var TranslatorExtension
     */
    protected $extension;
    /**
     * @var ObjectProphecy
     */
    protected $translator;

    public function setUp()
    {
        $this->translator = $this->prophesize(Translator::class);
        $this->extension = new TranslatorExtension($this->translator->reveal());
    }

    /**
     * @test
     */
    public function extensionNameIsClassName()
    {
        $this->assertEquals(TranslatorExtension::class, $this->extension->getName());
    }

    /**
     * @test
     */
    public function properFunctionsAreReturned()
    {
        $funcs = $this->extension->getFunctions();
        $this->assertCount(2, $funcs);
        foreach ($funcs as $func) {
            $this->assertInstanceOf(\Twig_SimpleFunction::class, $func);
        }
    }

    /**
     * @test
     */
    public function translateFallbacksToTranslator()
    {
        $this->translator->translate('foo', 'default', null)->shouldBeCalledTimes(1);
        $this->extension->translate('foo');

        $this->translator->translate('bar', 'baz', 'en')->shouldBeCalledTimes(1);
        $this->extension->translate('bar', 'baz', 'en');
    }

    /**
     * @test
     */
    public function translatePluralFallbacksToTranslator()
    {
        $this->translator->translatePlural('foo', 'bar', 'baz', 'default', null)->shouldBeCalledTimes(1);
        $this->extension->translatePlural('foo', 'bar', 'baz');

        $this->translator->translatePlural('foo', 'bar', 'baz', 'another', 'en')->shouldBeCalledTimes(1);
        $this->extension->translatePlural('foo', 'bar', 'baz', 'another', 'en');
    }
}
