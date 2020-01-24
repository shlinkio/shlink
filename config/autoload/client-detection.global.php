<?php

declare(strict_types=1);

return [

    'ip_address_resolution' => [
        'headers_to_inspect' => [
            'CF-Connecting-IP',
            'X-Forwarded-For',
            'X-Forwarded',
            'Forwarded',
            'True-Client-IP',
            'X-Real-IP',
            'X-Cluster-Client-Ip',
            'Client-Ip',
        ],
    ],

];
