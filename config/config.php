<?php

declare(strict_types=1);

return [
    'cache' => false,

    // Enable debugging; typically used to provide debugging information within templates.
    'debug' => false,

    'allowCurrencies' => [
        'USD', 'EUR', 'RUB', 'GBP'
    ],

    'db' => [
        'dsn' => 'mysql:dbname=apilog;host=localhost;port=3306',
        'username' => 'root',
        'password' => '',
    ],
];
