<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Installer;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Installer\Config\Plugin\ApplicationConfigCustomizer;
use Shlinkio\Shlink\Installer\Config\Plugin\LanguageConfigCustomizer;
use Shlinkio\Shlink\Installer\Model\CustomizableAppConfig;

class CustomizableAppConfigTest extends TestCase
{
    /**
     * @test
     */
    public function exchangeArrayIgnoresAnyNonProvidedKey()
    {
        $config = new CustomizableAppConfig();

        $config->exchangeArray([
            'app_options' => [
                'disable_track_param' => null,
            ],
            'translator' => [
                'locale' => 'es',
            ],
        ]);

        $this->assertFalse($config->hasDatabase());
        $this->assertFalse($config->hasUrlShortener());
        $this->assertTrue($config->hasApp());
        $this->assertTrue($config->hasLanguage());
        $this->assertEquals([
            ApplicationConfigCustomizer::DISABLE_TRACK_PARAM => null,
        ], $config->getApp());
        $this->assertEquals([
            LanguageConfigCustomizer::DEFAULT_LANG => 'es',
        ], $config->getLanguage());
    }
}
