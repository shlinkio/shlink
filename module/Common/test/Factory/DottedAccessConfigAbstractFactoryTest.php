<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Factory;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Common\Factory\DottedAccessConfigAbstractFactory;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\ServiceManager;

class DottedAccessConfigAbstractFactoryTest extends TestCase
{
    /** @var DottedAccessConfigAbstractFactory */
    private $factory;

    public function setUp(): void
    {
        $this->factory = new DottedAccessConfigAbstractFactory();
    }

    /**
     * @param string $serviceName
     * @param bool $canCreate
     *
     * @test
     * @dataProvider provideDotNames
     */
    public function canCreateOnlyServicesWithDot(string $serviceName, bool $canCreate)
    {
        $this->assertEquals($canCreate, $this->factory->canCreate(new ServiceManager(), $serviceName));
    }

    public function provideDotNames(): array
    {
        return [
            ['foo.bar', true],
            ['config.something', true],
            ['config_something', false],
            ['foo', false],
        ];
    }

    /**
     * @test
     */
    public function throwsExceptionWhenFirstPartOfTheServiceIsNotRegistered()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage(
            'Defined service "foo" could not be found in container after resolving dotted expression "foo.bar"'
        );

        $this->factory->__invoke(new ServiceManager(), 'foo.bar');
    }

    /**
     * @test
     */
    public function dottedNotationIsRecursivelyResolvedUntilLastValueIsFoundAndReturned()
    {
        $expected = 'this is the result';

        $result = $this->factory->__invoke(new ServiceManager(['services' => [
            'foo' => [
                'bar' => ['baz' => $expected],
            ],
        ]]), 'foo.bar.baz');

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function exceptionIsThrownIfAnyStepCannotBeResolved()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The key "baz" provided in the dotted notation could not be found in the array service'
        );

        $this->factory->__invoke(new ServiceManager(['services' => [
            'foo' => [
                'bar' => ['something' => 123],
            ],
        ]]), 'foo.bar.baz');
    }
}
