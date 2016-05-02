<?php
return [

    'database' => [
        'driver' => 'pdo_mysql',
        'user' => getenv('DB_USER'),
        'password' => getenv('DB_PASSWORD'),
        'dbname' => getenv('DB_NAME') ?: 'acelaya_url_shortener',
        'charset' => 'utf8',
        'driverOptions' => [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
        ],
    ],

];
