<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Fixtures;

use Cake\Chronos\Chronos;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use ReflectionObject;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\PersistenceShortUrlRelationResolver;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ShortUrlsFixture extends AbstractFixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [ApiKeyFixture::class, TagsFixture::class];
    }

    public function load(ObjectManager $manager): void
    {
        $relationResolver = new PersistenceShortUrlRelationResolver($manager); // @phpstan-ignore-line

        /** @var ApiKey $authorApiKey */
        $authorApiKey = $this->getReference('author_api_key');

        $abcShortUrl = $this->setShortUrlDate(
            ShortUrl::create(new ShortUrlCreation(
                longUrl: 'https://shlink.io',
                customSlug: 'abc123',
                maxVisits: 2,
                apiKey: $authorApiKey,
                tags: ['foo'],
                title: 'My cool title',
                crawlable: true,
            ), $relationResolver),
            '2018-05-01',
        );
        $manager->persist($abcShortUrl);

        $defShortUrl = $this->setShortUrlDate(ShortUrl::create(new ShortUrlCreation(
            longUrl:
                'https://blog.alejandrocelaya.com/2017/12/09/acmailer-7-0-the-most-important-release-in-a-long-time/',
            validSince: Chronos::parse('2020-05-01'),
            customSlug: 'def456',
            apiKey: $authorApiKey,
            tags: ['foo', 'bar'],
        ), $relationResolver), '2019-01-01 00:00:10');
        $manager->persist($defShortUrl);

        $customShortUrl = $this->setShortUrlDate(ShortUrl::create(new ShortUrlCreation(
            longUrl: 'https://shlink.io',
            customSlug: 'custom',
            maxVisits: 2,
            apiKey: $authorApiKey,
            crawlable: true,
            forwardQuery: false,
        )), '2019-01-01 00:00:20');
        $manager->persist($customShortUrl);

        $ghiShortUrl = $this->setShortUrlDate(
            ShortUrl::create(new ShortUrlCreation(
                longUrl: 'https://shlink.io/documentation/',
                validUntil: Chronos::parse('2020-05-01'), // In the past
                customSlug: 'ghi789',
            )),
            '2018-05-01',
        );
        $manager->persist($ghiShortUrl);

        $withDomainDuplicatingShortCode = $this->setShortUrlDate(ShortUrl::create(new ShortUrlCreation(
            longUrl: 'https://blog.alejandrocelaya.com/2019/04/27/considerations-to-properly-use-open-'
                . 'source-software-projects/',
            customSlug: 'ghi789',
            domain: 'example.com',
            tags: ['foo'],
        ), $relationResolver), '2019-01-01 00:00:30');
        $manager->persist($withDomainDuplicatingShortCode);

        $withDomainAndSlugShortUrl = $this->setShortUrlDate(ShortUrl::create(
            new ShortUrlCreation('https://google.com', customSlug: 'custom-with-domain', domain: 'some-domain.com'),
        ), '2018-10-20');
        $manager->persist($withDomainAndSlugShortUrl);

        $manager->flush();

        $this->addReference('abc123_short_url', $abcShortUrl);
        $this->addReference('def456_short_url', $defShortUrl);
        $this->addReference('ghi789_short_url', $ghiShortUrl);
        $this->addReference('example_short_url', $withDomainDuplicatingShortCode);
    }

    private function setShortUrlDate(ShortUrl $shortUrl, string $date): ShortUrl
    {
        $ref = new ReflectionObject($shortUrl);
        $dateProp = $ref->getProperty('dateCreated');
        $dateProp->setValue($shortUrl, Chronos::parse($date));

        return $shortUrl;
    }
}
