<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Rest;

interface DataTransformerInterface
{
    public function transform($value): array;
}
