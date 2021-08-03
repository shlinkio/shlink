<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Shlinkio\Shlink\Core\Config\NotFoundRedirects;
use Shlinkio\Shlink\Core\Entity\Domain;

class DomainFixture extends AbstractFixture
{
    public function load(ObjectManager $manager): void
    {
        $domain = Domain::withAuthority('example.com');
        $manager->persist($domain);
        $this->addReference('example_domain', $domain);

        $manager->persist(Domain::withAuthority('this_domain_is_detached.com'));

        $detachedWithRedirects = Domain::withAuthority('detached-with-redirects.com');
        $detachedWithRedirects->configureNotFoundRedirects(NotFoundRedirects::withRedirects('foo.com', 'bar.com'));
        $manager->persist($detachedWithRedirects);

        $manager->flush();
    }
}
