<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Datum Master Switch
    |--------------------------------------------------------------------------
    |
    | This configuration option may be used to completely disable all Datum
    | data recorders regardless of their individual configurations. This
    | provides a single option to quickly disable all Datum recording.
    |
    */

    'enabled' => env('DATUM_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Datum Storage Driver
    |--------------------------------------------------------------------------
    |
    | This configuration option determines which storage driver will be used
    | while storing entries from Datum's recorders. In addition, you also
    | may provide any options to configure the selected storage driver.
    |
    */

    'storage' => [
        'driver' => env('DATUM_STORAGE_DRIVER', 'database'),

        'database' => [
            'connection' => env('DATUM_DB_CONNECTION', 'tenant'),
            'chunk' => 1000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Datum Ingest Driver
    |--------------------------------------------------------------------------
    |
    | This configuration options determines the ingest driver that will be used
    | to capture entries from Datum's recorders. Ingest drivers are great to
    | free up your request workers quickly by offloading the data storage.
    |
    */

    'ingest' => [
        'driver' => env('DATUM_INGEST_DRIVER', 'storage'),

        'buffer' => env('DATUM_INGEST_BUFFER', 5000),

        'trim' => [
            'lottery' => [1, 1_000],
            'keep' => '365 days',
        ],

        'redis' => [
            'connection' => env('DATUM_REDIS_CONNECTION'),
            'chunk' => 1000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Datum Cache Driver
    |--------------------------------------------------------------------------
    |
    | This configuration option determines the cache driver that will be used
    | for various tasks, including caching dashboard results, establishing
    | locks for events that should only occur on one server and signals.
    |
    */

    'cache' => env('DATUM_CACHE_DRIVER'),

    /*
    |--------------------------------------------------------------------------
    | Datum Recorders
    |--------------------------------------------------------------------------
    |
    | The following array lists the "recorders" that will be registered with
    | Datum, along with their configuration. Recorders gather application
    | event data from requests and tasks to pass to your ingest driver.
    |
    */

    'recorders' => [
        //        Recorders\ExampleRecord::class => [
        //            'enabled' => env('YOUR_RECORDER_ENABLED', true),
        //            'sample_rate' => env('DATUM_USER_REQUESTS_SAMPLE_RATE', 1),
        //            'ignore' => [
        //                '#^/datum$#', // Datum dashboard...
        //                '#^/telescope#', // Telescope dashboard...
        //            ],
        //        ],
    ],
];
