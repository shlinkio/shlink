<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use Shlinkio\Shlink\Common\Doctrine\ReopeningEntityManagerDelegator;
use Zend\ServiceManager\ServiceManager;

class ReopeningEntityManagerDelegatorTest extends TestCase
{
    /** @test */
    public function decoratesEntityManagerFromCallback(): void
    {
        $em = $this->prophesize(EntityManagerInterface::class)->reveal();
        $result = (new ReopeningEntityManagerDelegator())(new ServiceManager(), '', function () use ($em) {
            return $em;
        });

        $ref = new ReflectionObject($result);
        $prop = $ref->getProperty('wrapped');
        $prop->setAccessible(true);

        $this->assertSame($em, $prop->getValue($result));
    }
}
