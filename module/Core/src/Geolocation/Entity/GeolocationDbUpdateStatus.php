<?php

declare(strict_types=1);


namespace Shlinkio\Shlink\Core\Geolocation\Entity;

enum GeolocationDbUpdateStatus: string
{
    case IN_PROGRESS = 'in-progress';
    case SUCCESS = 'success';
    case ERROR = 'error';
}
