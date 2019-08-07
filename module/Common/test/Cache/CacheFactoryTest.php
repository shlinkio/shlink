<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Cache;

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Cache\CacheFactory;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Zend\ServiceManager\ServiceManager;

use function putenv;

class CacheFactoryTest extends TestCase
{
    /** @var CacheFactory */
    private $factory;
    /** @var ServiceManager */
    private $sm;

    public function setUp(): void
    {
        $this->factory = new CacheFactory();
        $this->sm = new ServiceManager(['services' => [
            AppOptions::class => new AppOptions(),
        ]]);
    }

    public static function tearDownAfterClass(): void
    {
        putenv('APP_ENV');
    }

    /** @test */
    public function productionReturnsApcAdapter(): void
    {
        putenv('APP_ENV=pro');
        $instance = ($this->factory)($this->sm, '');
        $this->assertInstanceOf(ApcuCache::class, $instance);
    }

    /** @test */
    public function developmentReturnsArrayAdapter(): void
    {
        putenv('APP_ENV=dev');
        $instance = ($this->factory)($this->sm, '');
        $this->assertInstanceOf(ArrayCache::class, $instance);
    }
}
