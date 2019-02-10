<?php
declare(strict_types=1);

use Shlinkio\Shlink\Installer\Config\Plugin;

return [

    'installer_plugins_expected_config' => [
        Plugin\LanguageConfigCustomizer::class => [
            Plugin\LanguageConfigCustomizer::DEFAULT_LANG,
        ],

        Plugin\UrlShortenerConfigCustomizer::class => [
            Plugin\UrlShortenerConfigCustomizer::SCHEMA,
            Plugin\UrlShortenerConfigCustomizer::HOSTNAME,
            Plugin\UrlShortenerConfigCustomizer::CHARS,
            Plugin\UrlShortenerConfigCustomizer::VALIDATE_URL,
            Plugin\UrlShortenerConfigCustomizer::ENABLE_NOT_FOUND_REDIRECTION,
            Plugin\UrlShortenerConfigCustomizer::NOT_FOUND_REDIRECT_TO,
        ],

        Plugin\ApplicationConfigCustomizer::class => [
            Plugin\ApplicationConfigCustomizer::SECRET,
            Plugin\ApplicationConfigCustomizer::DISABLE_TRACK_PARAM,
            Plugin\ApplicationConfigCustomizer::CHECK_VISITS_THRESHOLD,
            Plugin\ApplicationConfigCustomizer::VISITS_THRESHOLD,
        ],

        Plugin\DatabaseConfigCustomizer::class => [
            Plugin\DatabaseConfigCustomizer::DRIVER,
            Plugin\DatabaseConfigCustomizer::NAME,
            Plugin\DatabaseConfigCustomizer::USER,
            Plugin\DatabaseConfigCustomizer::PASSWORD,
            Plugin\DatabaseConfigCustomizer::HOST,
            Plugin\DatabaseConfigCustomizer::PORT,
        ],
    ],

];
