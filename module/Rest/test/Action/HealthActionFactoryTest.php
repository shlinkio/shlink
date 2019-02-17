<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Shlinkio\Shlink\Rest\Action;
use Zend\ServiceManager\ServiceManager;

class HealthActionFactoryTest extends TestCase
{
    /** @var Action\HealthActionFactory */
    private $factory;

    public function setUp(): void
    {
        $this->factory = new Action\HealthActionFactory();
    }

    /** @test */
    public function serviceIsCreatedExtractingConnectionFromEntityManager()
    {
        $em = $this->prophesize(EntityManager::class);
        $conn = $this->prophesize(Connection::class);

        $getConnection = $em->getConnection()->willReturn($conn->reveal());

        $sm = new ServiceManager(['services' => [
            'Logger_Shlink' => $this->prophesize(LoggerInterface::class)->reveal(),
            AppOptions::class => $this->prophesize(AppOptions::class)->reveal(),
            EntityManager::class => $em->reveal(),
        ]]);

        $instance = ($this->factory)($sm, '');

        $this->assertInstanceOf(Action\HealthAction::class, $instance);
        $getConnection->shouldHaveBeenCalledOnce();
    }
}
