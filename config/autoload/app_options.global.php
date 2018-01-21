<?php
declare(strict_types=1);

use function Shlinkio\Shlink\Common\env;

return [

    'app_options' => [
        'name' => 'Shlink',
        'version' => '1.7.0',
        'secret_key' => env('SECRET_KEY'),
    ],

];
