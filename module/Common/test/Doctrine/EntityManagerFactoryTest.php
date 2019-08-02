<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Doctrine;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Doctrine\EntityManagerFactory;
use Shlinkio\Shlink\Common\Type\ChronosDateTimeType;
use Zend\ServiceManager\ServiceManager;

class EntityManagerFactoryTest extends TestCase
{
    /** @var EntityManagerFactory */
    private $factory;

    public function setUp(): void
    {
        $this->factory = new EntityManagerFactory();
    }

    /** @test */
    public function serviceIsCreated(): void
    {
        $sm = new ServiceManager(['services' => [
            'config' => [
                'debug' => true,
                'entity_manager' => [
                    'orm' => [
                        'types' => [
                            ChronosDateTimeType::CHRONOS_DATETIME => ChronosDateTimeType::class,
                        ],
                    ],
                    'connection' => [
                        'driver' => 'pdo_sqlite',
                    ],
                ],
            ],
        ]]);

        $em = ($this->factory)($sm, EntityManager::class);
        $this->assertInstanceOf(EntityManager::class, $em);
    }
}
