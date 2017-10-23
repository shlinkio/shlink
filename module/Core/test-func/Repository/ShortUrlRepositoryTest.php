<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;
use ShlinkioTest\Shlink\Common\DbUnit\DatabaseTestCase;

class ShortUrlRepositoryTest extends DatabaseTestCase
{
    const ENTITIES_TO_EMPTY = [
        ShortUrl::class,
        Visit::class,
    ];

    /**
     * @var ShortUrlRepository
     */
    private $repo;

    public function setUp()
    {
        $this->repo = $this->getEntityManager()->getRepository(ShortUrl::class);
    }

    /**
     * @test
     */
    public function findOneByShortCodeReturnsProperData()
    {
        $foo = new ShortUrl();
        $foo->setOriginalUrl('foo')
            ->setShortCode('foo');
        $this->getEntityManager()->persist($foo);

        $bar = new ShortUrl();
        $bar->setOriginalUrl('bar')
            ->setShortCode('bar')
            ->setValidSince((new \DateTime())->add(new \DateInterval('P1M')));
        $this->getEntityManager()->persist($bar);

        $visits = [];
        for ($i = 0; $i < 3; $i++) {
            $visit = new Visit();
            $this->getEntityManager()->persist($visit);
            $visits[] = $visit;
        }
        $baz = new ShortUrl();
        $baz->setOriginalUrl('baz')
            ->setShortCode('baz')
            ->setVisits(new ArrayCollection($visits))
            ->setMaxVisits(3);
        $this->getEntityManager()->persist($baz);

        $this->getEntityManager()->flush();

        $this->assertSame($foo, $this->repo->findOneByShortCode($foo->getShortCode()));
        $this->assertNull($this->repo->findOneByShortCode('invalid'));
        $this->assertNull($this->repo->findOneByShortCode($bar->getShortCode()));
        $this->assertNull($this->repo->findOneByShortCode($baz->getShortCode()));
    }
}
