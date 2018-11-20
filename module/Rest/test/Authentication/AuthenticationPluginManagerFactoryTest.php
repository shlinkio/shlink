<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Authentication;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Rest\Authentication\AuthenticationPluginManager;
use Shlinkio\Shlink\Rest\Authentication\AuthenticationPluginManagerFactory;
use Zend\ServiceManager\ServiceManager;

class AuthenticationPluginManagerFactoryTest extends TestCase
{
    /** @var AuthenticationPluginManagerFactory */
    private $factory;

    public function setUp()
    {
        $this->factory = new AuthenticationPluginManagerFactory();
    }

    /**
     * @test
     */
    public function serviceIsProperlyCreated()
    {
        $instance = $this->factory->__invoke(new ServiceManager(['services' => [
            'config' => [],
        ]]), '');
        $this->assertInstanceOf(AuthenticationPluginManager::class, $instance);
    }
}
