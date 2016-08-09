<?php
namespace ShlinkioTest\Shlink\Common\Factory;

use Doctrine\ORM\EntityManager;
use PHPUnit_Framework_TestCase as TestCase;
use Shlinkio\Shlink\Common\Factory\EntityManagerFactory;
use Zend\ServiceManager\ServiceManager;

class EntityManagerFactoryTest extends TestCase
{
    /**
     * @var EntityManagerFactory
     */
    protected $factory;

    public function setUp()
    {
        $this->factory = new EntityManagerFactory();
    }

    /**
     * @test
     */
    public function serviceIsCreated()
    {
        $sm = new ServiceManager(['services' => [
            'config' => [
                'debug' => true,
                'entity_manager' => [
                    'connection' => [
                        'driver' => 'pdo_sqlite',
                    ],
                ],
            ],
        ]]);

        $em = $this->factory->__invoke($sm, EntityManager::class);
        $this->assertInstanceOf(EntityManager::class, $em);
    }
}
