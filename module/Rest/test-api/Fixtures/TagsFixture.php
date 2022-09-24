<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Shlinkio\Shlink\Core\Tag\Entity\Tag;

class TagsFixture extends AbstractFixture
{
    public function load(ObjectManager $manager): void
    {
        $manager->persist(new Tag('foo'));
        $manager->persist(new Tag('bar'));
        $manager->persist(new Tag('baz'));

        $manager->flush();
    }
}
