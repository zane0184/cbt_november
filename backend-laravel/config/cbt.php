<?php

return [
    'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),
    // Must match APP_TIMEZONE so MySQL TIMESTAMP values round-trip consistently.
    'database_timezone' => env('DB_TIMEZONE', '+07:00'),
];
