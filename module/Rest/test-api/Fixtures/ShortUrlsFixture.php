<?php
declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Fixtures;

use Cake\Chronos\Chronos;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;

class ShortUrlsFixture extends AbstractFixture
{
    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $abcShortUrl = (new ShortUrl('https://shlink.io'))->setShortCode('abc123');
        $manager->persist($abcShortUrl);

        $defShortUrl = (new ShortUrl(
            'https://shlink.io',
            ShortUrlMeta::createFromParams(Chronos::now()->addDays(3))
        ))->setShortCode('def456');
        $manager->persist($defShortUrl);

        $customShortUrl = new ShortUrl(
            'https://shlink.io',
            ShortUrlMeta::createFromParams(null, null, 'custom', 2)
        );
        $manager->persist($customShortUrl);

        $manager->flush();

        $this->addReference('abc123_short_url', $abcShortUrl);
        $this->addReference('def456_short_url', $defShortUrl);
    }
}
