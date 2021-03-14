<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Fixtures;

use Cake\Chronos\Chronos;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use ReflectionObject;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ApiKeyFixture extends AbstractFixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [DomainFixture::class];
    }

    public function load(ObjectManager $manager): void
    {
        $manager->persist($this->buildApiKey('valid_api_key', true));
        $manager->persist($this->buildApiKey('disabled_api_key', false));
        $manager->persist($this->buildApiKey('expired_api_key', true, Chronos::now()->subDay()));

        $authorApiKey = $this->buildApiKey('author_api_key', true);
        $authorApiKey->registerRole(RoleDefinition::forAuthoredShortUrls());
        $manager->persist($authorApiKey);
        $this->addReference('author_api_key', $authorApiKey);

        /** @var Domain $exampleDomain */
        $exampleDomain = $this->getReference('example_domain');
        $domainApiKey = $this->buildApiKey('domain_api_key', true);
        $domainApiKey->registerRole(RoleDefinition::forDomain($exampleDomain));
        $manager->persist($domainApiKey);

        $manager->flush();
    }

    private function buildApiKey(string $key, bool $enabled, ?Chronos $expiresAt = null): ApiKey
    {
        $apiKey = $expiresAt !== null ? ApiKey::fromMeta(ApiKeyMeta::withExpirationDate($expiresAt)) : ApiKey::create();
        $ref = new ReflectionObject($apiKey);
        $keyProp = $ref->getProperty('key');
        $keyProp->setAccessible(true);
        $keyProp->setValue($apiKey, $key);

        if (! $enabled) {
            $apiKey->disable();
        }

        return $apiKey;
    }
}
