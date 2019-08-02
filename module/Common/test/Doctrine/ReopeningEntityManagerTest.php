<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Doctrine;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Doctrine\ReopeningEntityManager;
use stdClass;

class ReopeningEntityManagerTest extends TestCase
{
    /** @var ReopeningEntityManager */
    private $decoratorEm;
    /** @var ObjectProphecy */
    private $wrapped;

    public function setUp(): void
    {
        $this->wrapped = $this->prophesize(EntityManagerInterface::class);
        $this->wrapped->getConnection()->willReturn($this->prophesize(Connection::class));
        $this->wrapped->getConfiguration()->willReturn($this->prophesize(Configuration::class));
        $this->wrapped->getEventManager()->willReturn($this->prophesize(EventManager::class));

        $wrappedMock = $this->wrapped->reveal();
        $this->decoratorEm = new ReopeningEntityManager($wrappedMock, function () use ($wrappedMock) {
            return $wrappedMock;
        });
    }

    /**
     * @test
     * @dataProvider provideMethodNames
     */
    public function wrappedInstanceIsTransparentlyCalledWhenItIsNotClosed(string $methodName): void
    {
        $method = $this->wrapped->__call($methodName, [Argument::cetera()])->willReturnArgument();
        $isOpen = $this->wrapped->isOpen()->willReturn(true);

        $this->decoratorEm->{$methodName}(new stdClass());

        $method->shouldHaveBeenCalledOnce();
        $isOpen->shouldHaveBeenCalledOnce();
        $this->wrapped->getConnection()->shouldNotHaveBeenCalled();
        $this->wrapped->getConfiguration()->shouldNotHaveBeenCalled();
        $this->wrapped->getEventManager()->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     * @dataProvider provideMethodNames
     */
    public function wrappedInstanceIsRecreatedWhenItIsClosed(string $methodName): void
    {
        $method = $this->wrapped->__call($methodName, [Argument::cetera()])->willReturnArgument();
        $isOpen = $this->wrapped->isOpen()->willReturn(false);

        $this->decoratorEm->{$methodName}(new stdClass());

        $method->shouldHaveBeenCalledOnce();
        $isOpen->shouldHaveBeenCalledOnce();
        $this->wrapped->getConnection()->shouldHaveBeenCalledOnce();
        $this->wrapped->getConfiguration()->shouldHaveBeenCalledOnce();
        $this->wrapped->getEventManager()->shouldHaveBeenCalledOnce();
    }

    public function provideMethodNames(): iterable
    {
        yield 'flush' => ['flush'];
        yield 'persist' => ['persist'];
        yield 'remove' => ['remove'];
        yield 'refresh' => ['refresh'];
        yield 'merge' => ['merge'];
    }
}
