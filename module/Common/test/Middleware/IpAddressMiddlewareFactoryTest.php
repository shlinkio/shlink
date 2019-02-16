<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Middleware;

use PHPUnit\Framework\TestCase;
use ReflectionObject;
use Shlinkio\Shlink\Common\Middleware\IpAddressMiddlewareFactory;
use Shlinkio\Shlink\Core\Model\Visitor;
use Zend\ServiceManager\ServiceManager;

class IpAddressMiddlewareFactoryTest extends TestCase
{
    private $factory;

    public function setUp(): void
    {
        $this->factory = new IpAddressMiddlewareFactory();
    }

    /**
     * @test
     */
    public function returnedInstanceIsProperlyConfigured()
    {
        $instance = $this->factory->__invoke(new ServiceManager(), '');

        $ref = new ReflectionObject($instance);
        $checkProxyHeaders = $ref->getProperty('checkProxyHeaders');
        $checkProxyHeaders->setAccessible(true);
        $trustedProxies = $ref->getProperty('trustedProxies');
        $trustedProxies->setAccessible(true);
        $attributeName = $ref->getProperty('attributeName');
        $attributeName->setAccessible(true);

        $this->assertTrue($checkProxyHeaders->getValue($instance));
        $this->assertEquals([], $trustedProxies->getValue($instance));
        $this->assertEquals(Visitor::REMOTE_ADDRESS_ATTR, $attributeName->getValue($instance));
    }
}
