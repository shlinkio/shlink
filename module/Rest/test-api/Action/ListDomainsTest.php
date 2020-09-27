<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class ListDomainsTest extends ApiTestCase
{
    /** @test */
    public function domainsAreProperlyListed(): void
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/domains');
        $respPayload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_OK, $resp->getStatusCode());
        self::assertEquals([
            'domains' => [
                'data' => [
                    [
                        'domain' => 'doma.in',
                        'isDefault' => true,
                    ],
                    [
                        'domain' => 'example.com',
                        'isDefault' => false,
                    ],
                    [
                        'domain' => 'some-domain.com',
                        'isDefault' => false,
                    ],
                ],
            ],
        ], $respPayload);
    }
}
