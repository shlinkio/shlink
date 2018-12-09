<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Model;

use JsonSerializable;

interface VisitLocationInterface extends JsonSerializable
{
    public function getCountryName(): string;

    public function getLatitude(): string;

    public function getLongitude(): string;

    public function getCityName(): string;
}
