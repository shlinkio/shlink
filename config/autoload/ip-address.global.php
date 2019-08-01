<?php
declare(strict_types=1);

return [

    'ip_address_resolution' => [
        'headers_to_inspect' => [
            'Forwarded',
            'X-Forwarded-For',
            'X-Forwarded',
            'X-Cluster-Client-Ip',
            'Client-Ip',
            'X-Real-IP',
            'CF-Connecting-IP',
            'True-Client-IP',
        ],
    ],

];
