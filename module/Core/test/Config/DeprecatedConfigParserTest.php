<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Config;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\DeprecatedConfigParser;

use function array_merge;

class DeprecatedConfigParserTest extends TestCase
{
    private DeprecatedConfigParser $postProcessor;

    public function setUp(): void
    {
        $this->postProcessor = new DeprecatedConfigParser();
    }

    /** @test */
    public function returnsConfigAsIsIfNewValueIsDefined(): void
    {
        $config = [
            'not_found_redirects' => [
                'invalid_short_url' => 'somewhere',
            ],
        ];

        $result = ($this->postProcessor)($config);

        $this->assertEquals($config, $result);
    }

    /** @test */
    public function doesNotProvideNewConfigIfOldOneIsDefinedButDisabled(): void
    {
        $config = [
            'url_shortener' => [
                'not_found_short_url' => [
                    'enable_redirection' => false,
                    'redirect_to' => 'somewhere',
                ],
            ],
        ];

        $result = ($this->postProcessor)($config);

        $this->assertEquals($config, $result);
    }

    /** @test */
    public function mapsOldConfigToNewOneWhenOldOneIsEnabled(): void
    {
        $config = [
            'url_shortener' => [
                'not_found_short_url' => [
                    'enable_redirection' => true,
                    'redirect_to' => 'somewhere',
                ],
            ],
        ];
        $expected = array_merge($config, [
            'not_found_redirects' => [
                'invalid_short_url' => 'somewhere',
            ],
        ]);

        $result = ($this->postProcessor)($config);

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function definesNewConfigAsNullIfOldOneIsEnabledWithNoRedirectValue(): void
    {
        $config = [
            'url_shortener' => [
                'not_found_short_url' => [
                    'enable_redirection' => true,
                ],
            ],
        ];
        $expected = array_merge($config, [
            'not_found_redirects' => [
                'invalid_short_url' => null,
            ],
        ]);

        $result = ($this->postProcessor)($config);

        $this->assertEquals($expected, $result);
    }
}
