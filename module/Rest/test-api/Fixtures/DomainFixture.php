<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Shlinkio\Shlink\Core\Entity\Domain;

class DomainFixture extends AbstractFixture
{
    public function load(ObjectManager $manager): void
    {
        $orphanDomain = new Domain('this_domain_is_detached.com');
        $manager->persist($orphanDomain);
        $manager->flush();
    }
}
