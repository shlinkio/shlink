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
     * @dataProvider provideConfigs
     */
    public function returnedInstanceIsProperlyConfigured(array $config, array $expectedHeadersToInspect): void
    {
        $instance = ($this->factory)(new ServiceManager(['services' => [
            'config' => $config,
        ]]), '');

        $ref = new ReflectionObject($instance);
        $checkProxyHeaders = $ref->getProperty('checkProxyHeaders');
        $checkProxyHeaders->setAccessible(true);
        $trustedProxies = $ref->getProperty('trustedProxies');
        $trustedProxies->setAccessible(true);
        $attributeName = $ref->getProperty('attributeName');
        $attributeName->setAccessible(true);
        $headersToInspect = $ref->getProperty('headersToInspect');
        $headersToInspect->setAccessible(true);

        $this->assertTrue($checkProxyHeaders->getValue($instance));
        $this->assertEquals([], $trustedProxies->getValue($instance));
        $this->assertEquals(Visitor::REMOTE_ADDRESS_ATTR, $attributeName->getValue($instance));
        $this->assertEquals($expectedHeadersToInspect, $headersToInspect->getValue($instance));
    }

    public function provideConfigs(): iterable
    {
        $defaultHeadersToInspect = [
            'Forwarded',
            'X-Forwarded-For',
            'X-Forwarded',
            'X-Cluster-Client-Ip',
            'Client-Ip',
        ];

        yield 'no ip_address_resolution config' => [[], $defaultHeadersToInspect];
        yield 'no headers_to_inspect config' => [['ip_address_resolution' => []], $defaultHeadersToInspect];
        yield 'null headers_to_inspect' => [['ip_address_resolution' => [
            'headers_to_inspect' => null,
        ]], $defaultHeadersToInspect];
        yield 'empty headers_to_inspect' => [['ip_address_resolution' => [
            'headers_to_inspect' => [],
        ]], $defaultHeadersToInspect];
        yield 'some headers_to_inspect' => [['ip_address_resolution' => [
            'headers_to_inspect' => [
                'foo',
                'bar',
                'baz',
            ],
        ]], [
            'foo',
            'bar',
            'baz',
        ]];
        yield 'some other headers_to_inspect' => [['ip_address_resolution' => [
            'headers_to_inspect' => [
                'something',
                'something_else',
            ],
        ]], [
            'something',
            'something_else',
        ]];
    }
}
