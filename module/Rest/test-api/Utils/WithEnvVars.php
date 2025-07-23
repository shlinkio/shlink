<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Utils;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class WithEnvVars
{
    public function __construct(public array $envVars)
    {
    }
}
