<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class DeleteOrphanVisitsTest extends ApiTestCase
{
    #[Test]
    public function deletesOrphanVisitsWithoutAffectingTheRest(): void
    {
        self::assertEquals(7, $this->getTotalVisits());
        self::assertEquals(3, $this->getOrphanVisits());

        $resp = $this->callApiWithKey(self::METHOD_DELETE, '/visits/orphan');
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(200, $resp->getStatusCode());
        self::assertEquals(3, $payload['deletedVisits']);
        self::assertEquals(7, $this->getTotalVisits()); // This verifies that regular visits have not been affected
        self::assertEquals(0, $this->getOrphanVisits());
    }

    #[Test]
    public function doesNotDeleteOrphanVisitsForRestrictedApiKey(): void
    {
        self::assertEquals(7, $this->getTotalVisits());
        self::assertEquals(3, $this->getOrphanVisits());

        $resp = $this->callApiWithKey(self::METHOD_DELETE, '/visits/orphan', apiKey: 'no_orphans_api_key');
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(200, $resp->getStatusCode());
        self::assertEquals(0, $payload['deletedVisits']);
        self::assertEquals(7, $this->getTotalVisits()); // This verifies that regular visits have not been affected
        self::assertEquals(3, $this->getOrphanVisits()); // This verifies that all orphan visits still exist
    }

    private function getTotalVisits(): int
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/visits/non-orphan');
        $payload = $this->getJsonResponsePayload($resp);

        return $payload['visits']['pagination']['totalItems'];
    }

    private function getOrphanVisits(): int
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/visits/orphan');
        $payload = $this->getJsonResponsePayload($resp);

        return $payload['visits']['pagination']['totalItems'];
    }
}
