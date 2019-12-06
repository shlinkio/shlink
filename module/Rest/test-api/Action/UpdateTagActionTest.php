<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use GuzzleHttp\RequestOptions;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class UpdateTagActionTest extends ApiTestCase
{
    /** @test */
    public function errorIsThrownWhenTryingToRenameTagToAnotherTagName(): void
    {
        $resp = $this->callApiWithKey(self::METHOD_PUT, '/tags', [RequestOptions::JSON => [
            'oldName' => 'foo',
            'newName' => 'bar',
        ]]);
        $payload = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_CONFLICT, $resp->getStatusCode());
        $this->assertEquals('TAG_CONFLICT', $payload['error']);
    }

    /** @test */
    public function tagIsProperlyRenamedWhenRenamingToItself(): void
    {
        $resp = $this->callApiWithKey(self::METHOD_PUT, '/tags', [RequestOptions::JSON => [
            'oldName' => 'foo',
            'newName' => 'foo',
        ]]);

        $this->assertEquals(self::STATUS_NO_CONTENT, $resp->getStatusCode());
    }
}
