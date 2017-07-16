<?php
use Shlinkio\Shlink\Common;

return [

    'app_options' => [
        'name' => 'Shlink',
        'version' => '1.2.0',
        'secret_key' => Common\env('SECRET_KEY'),
    ],

];
