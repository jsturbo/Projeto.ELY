<?php

return [
    'default' => env('CACHE_STORE', 'file'),
    'stores'  => [
        'file' => [
            'driver' => 'file',
            'path'   => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],
        'array' => ['driver' => 'array', 'serialize' => false],
        'database' => ['driver' => 'database', 'connection' => null, 'table' => 'cache', 'lock_table' => 'cache_locks'],
        'null' => ['driver' => 'null'],
    ],
    'prefix' => env('CACHE_PREFIX', 'restaurante_cache'),
];
