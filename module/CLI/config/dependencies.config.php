<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI;

use Shlinkio\Shlink\Common\IpGeolocation\GeoLite2\DbUpdater;
use Shlinkio\Shlink\Common\IpGeolocation\IpLocationResolverInterface;
use Shlinkio\Shlink\Common\Service\PreviewGenerator;
use Shlinkio\Shlink\Core\Service;
use Shlinkio\Shlink\Rest\Service\ApiKeyService;
use Symfony\Component\Console\Application;
use Symfony\Component\Lock;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Zend\ServiceManager\Factory\InvokableFactory;

return [

    'dependencies' => [
        'factories' => [
            Application::class => Factory\ApplicationFactory::class,

            Command\ShortUrl\GenerateShortUrlCommand::class => ConfigAbstractFactory::class,
            Command\ShortUrl\ResolveUrlCommand::class => ConfigAbstractFactory::class,
            Command\ShortUrl\ListShortUrlsCommand::class => ConfigAbstractFactory::class,
            Command\ShortUrl\GetVisitsCommand::class => ConfigAbstractFactory::class,
            Command\ShortUrl\GeneratePreviewCommand::class => ConfigAbstractFactory::class,
            Command\ShortUrl\DeleteShortUrlCommand::class => ConfigAbstractFactory::class,

            Command\Visit\ProcessVisitsCommand::class => ConfigAbstractFactory::class,
            Command\Visit\UpdateDbCommand::class => ConfigAbstractFactory::class,

            Command\Config\GenerateCharsetCommand::class => InvokableFactory::class,
            Command\Config\GenerateSecretCommand::class => InvokableFactory::class,

            Command\Api\GenerateKeyCommand::class => ConfigAbstractFactory::class,
            Command\Api\DisableKeyCommand::class => ConfigAbstractFactory::class,
            Command\Api\ListKeysCommand::class => ConfigAbstractFactory::class,

            Command\Tag\ListTagsCommand::class => ConfigAbstractFactory::class,
            Command\Tag\CreateTagCommand::class => ConfigAbstractFactory::class,
            Command\Tag\RenameTagCommand::class => ConfigAbstractFactory::class,
            Command\Tag\DeleteTagsCommand::class => ConfigAbstractFactory::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        Command\ShortUrl\GenerateShortUrlCommand::class => [Service\UrlShortener::class, 'config.url_shortener.domain'],
        Command\ShortUrl\ResolveUrlCommand::class => [Service\UrlShortener::class],
        Command\ShortUrl\ListShortUrlsCommand::class => [Service\ShortUrlService::class, 'config.url_shortener.domain'],
        Command\ShortUrl\GetVisitsCommand::class => [Service\VisitsTracker::class],
        Command\ShortUrl\GeneratePreviewCommand::class => [Service\ShortUrlService::class, PreviewGenerator::class],
        Command\ShortUrl\DeleteShortUrlCommand::class => [Service\ShortUrl\DeleteShortUrlService::class],

        Command\Visit\ProcessVisitsCommand::class => [
            Service\VisitService::class,
            IpLocationResolverInterface::class,
            Lock\Factory::class,
        ],
        Command\Visit\UpdateDbCommand::class => [DbUpdater::class],

        Command\Api\GenerateKeyCommand::class => [ApiKeyService::class],
        Command\Api\DisableKeyCommand::class => [ApiKeyService::class],
        Command\Api\ListKeysCommand::class => [ApiKeyService::class],

        Command\Tag\ListTagsCommand::class => [Service\Tag\TagService::class],
        Command\Tag\CreateTagCommand::class => [Service\Tag\TagService::class],
        Command\Tag\RenameTagCommand::class => [Service\Tag\TagService::class],
        Command\Tag\DeleteTagsCommand::class => [Service\Tag\TagService::class],
    ],

];
