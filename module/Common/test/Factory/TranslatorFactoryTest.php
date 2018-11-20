<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Factory;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Factory\TranslatorFactory;
use Zend\I18n\Translator\Translator;
use Zend\ServiceManager\ServiceManager;

class TranslatorFactoryTest extends TestCase
{
    /** @var TranslatorFactory */
    protected $factory;

    public function setUp()
    {
        $this->factory = new TranslatorFactory();
    }

    /**
     * @test
     */
    public function serviceIsCreated()
    {
        $instance = $this->factory->__invoke(new ServiceManager(['services' => [
            'config' => [],
        ]]), '');
        $this->assertInstanceOf(Translator::class, $instance);
    }
}
