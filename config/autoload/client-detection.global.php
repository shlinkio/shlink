<?php

declare(strict_types=1);

return [

    'ip_address_resolution' => [
        'headers_to_inspect' => [
            'CF-Connecting-IP',
            'True-Client-IP',
            'X-Real-IP',
            'Forwarded',
            'X-Forwarded-For',
            'X-Forwarded',
            'X-Cluster-Client-Ip',
            'Client-Ip',
        ],
    ],

];
