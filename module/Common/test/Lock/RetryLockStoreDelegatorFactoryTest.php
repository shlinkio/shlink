<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Lock;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionObject;
use Shlinkio\Shlink\Common\Lock\RetryLockStoreDelegatorFactory;
use Symfony\Component\Lock\StoreInterface;
use Zend\ServiceManager\ServiceManager;

class RetryLockStoreDelegatorFactoryTest extends TestCase
{
    /** @var RetryLockStoreDelegatorFactory */
    private $delegator;
    /** @var ObjectProphecy */
    private $originalStore;

    public function setUp(): void
    {
        $this->originalStore = $this->prophesize(StoreInterface::class)->reveal();
        $this->delegator = new RetryLockStoreDelegatorFactory();
    }

    /** @test */
    public function originalStoreIsWrappedInRetryStore(): void
    {
        $callback = function () {
            return $this->originalStore;
        };

        $result = ($this->delegator)(new ServiceManager(), '', $callback);

        $ref = new ReflectionObject($result);
        $prop = $ref->getProperty('decorated');
        $prop->setAccessible(true);

        $this->assertSame($this->originalStore, $prop->getValue($result));
    }
}
