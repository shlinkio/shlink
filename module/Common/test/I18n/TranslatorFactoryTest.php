<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\I18n;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\I18n\TranslatorFactory;
use Zend\I18n\Translator\Translator;
use Zend\ServiceManager\ServiceManager;

class TranslatorFactoryTest extends TestCase
{
    /** @var TranslatorFactory */
    private $factory;

    public function setUp(): void
    {
        $this->factory = new TranslatorFactory();
    }

    /** @test */
    public function serviceIsCreated(): void
    {
        $instance = ($this->factory)(new ServiceManager(['services' => [
            'config' => [],
        ]]));
        $this->assertInstanceOf(Translator::class, $instance);
    }
}
