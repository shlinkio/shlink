<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\RedirectRule\Entity\RedirectCondition;
use Shlinkio\Shlink\Core\RedirectRule\Entity\ShortUrlRedirectRule;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

class ShortUrlRedirectRulesFixture extends AbstractFixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [ShortUrlsFixture::class];
    }

    public function load(ObjectManager $manager): void
    {
        /** @var ShortUrl $defShortUrl */
        $defShortUrl = $this->getReference('def456_short_url');

        // Create rules disordered to make sure the order by priority works
        $multipleQueryParamsRule = new ShortUrlRedirectRule(
            shortUrl: $defShortUrl,
            priority: 2,
            longUrl: 'https://example.com/multiple-query-params',
            conditions: new ArrayCollection(
                [RedirectCondition::forQueryParam('hello', 'world'), RedirectCondition::forQueryParam('foo', 'bar')],
            ),
        );
        $manager->persist($multipleQueryParamsRule);

        $englishAndFooQueryRule = new ShortUrlRedirectRule(
            shortUrl: $defShortUrl,
            priority: 1,
            longUrl: 'https://example.com/english-and-foo-query',
            conditions: new ArrayCollection(
                [RedirectCondition::forLanguage('en'), RedirectCondition::forAnyValueQueryParam('foo')],
            ),
        );
        $manager->persist($englishAndFooQueryRule);

        $androidRule = new ShortUrlRedirectRule(
            shortUrl: $defShortUrl,
            priority: 4,
            longUrl: 'android://foo/bar',
            conditions: new ArrayCollection([RedirectCondition::forDevice(DeviceType::ANDROID)]),
        );
        $manager->persist($androidRule);

        $onlyEnglishRule = new ShortUrlRedirectRule(
            shortUrl: $defShortUrl,
            priority: 3,
            longUrl: 'https://example.com/only-english',
            conditions: new ArrayCollection([RedirectCondition::forLanguage('en')]),
        );
        $manager->persist($onlyEnglishRule);

        $iosRule = new ShortUrlRedirectRule(
            shortUrl: $defShortUrl,
            priority: 5,
            longUrl: 'fb://profile/33138223345',
            conditions: new ArrayCollection([RedirectCondition::forDevice(DeviceType::IOS)]),
        );
        $manager->persist($iosRule);

        $ipAddressRule = new ShortUrlRedirectRule(
            shortUrl: $defShortUrl,
            priority: 6,
            longUrl: 'https://example.com/static-ip-address',
            conditions: new ArrayCollection([RedirectCondition::forIpAddress('1.2.3.4')]),
        );
        $manager->persist($ipAddressRule);

        $linuxRule = new ShortUrlRedirectRule(
            shortUrl: $defShortUrl,
            priority: 7,
            longUrl: 'https://example.com/linux',
            conditions: new ArrayCollection([RedirectCondition::forDevice(DeviceType::LINUX)]),
        );
        $manager->persist($linuxRule);

        $manager->flush();
    }
}
