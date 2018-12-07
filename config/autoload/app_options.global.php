<?php
declare(strict_types=1);

use function Shlinkio\Shlink\Common\env;

return [

    'app_options' => [
        'name' => 'Shlink',
        'version' => '%SHLINK_VERSION%',
        'secret_key' => env('SECRET_KEY', ''),
        'disable_track_param' => null,
    ],

];
