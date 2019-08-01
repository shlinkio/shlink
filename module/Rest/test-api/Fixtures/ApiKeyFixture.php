<?php
declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Fixtures;

use Cake\Chronos\Chronos;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use ReflectionObject;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ApiKeyFixture implements FixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $manager->persist($this->buildApiKey('valid_api_key', true));
        $manager->persist($this->buildApiKey('disabled_api_key', false));
        $manager->persist($this->buildApiKey('expired_api_key', true, Chronos::now()->subDay()));
        $manager->flush();
    }

    private function buildApiKey(string $key, bool $enabled, ?Chronos $expiresAt = null): ApiKey
    {
        $apiKey = new ApiKey($expiresAt);
        $refObj = new ReflectionObject($apiKey);
        $keyProp = $refObj->getProperty('key');
        $keyProp->setAccessible(true);
        $keyProp->setValue($apiKey, $key);

        if (! $enabled) {
            $apiKey->disable();
        }

        return $apiKey;
    }
}
