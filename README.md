# Datum recording data and aggregations for dashboards

[![Latest Version on Packagist](https://img.shields.io/packagist/v/vulpecula-io/laravel-datum.svg?style=flat-square)](https://packagist.org/packages/vulpecula-io/laravel-datum)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/vulpecula-io/laravel-datum/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/vulpecula-io/laravel-datum/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/vulpecula-io/laravel-datum/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/vulpecula-io/laravel-datum/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/vulpecula-io/laravel-datum.svg?style=flat-square)](https://packagist.org/packages/vulpecula-io/laravel-datum)

This leans heavily on the [Laravel Pulse](https://pulse.laravel.com) package for the general idea of how to obtain and process the data, but it's reworked to provide alternative and longer periods and be frontend agnostic. There is no interface built in - that's for you to do and put together, but the base functionality of aggregating data and pulling the data is there for you to use.

## Installation

You can install the package via composer:

```bash
composer require vulpecula-io/laravel-datum
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="datum-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="datum-config"
```

This is the contents of the published config file:

```php
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
```

## Recorders
Just as in Laravel Pulse, you need to create your own custom recorders to handle the data 'acquisition'. You can review the code of Pulse to see some examples of how to write a custom recorder [here](https://github.com/laravel/pulse/tree/1.x/src/Recorders).

> [!NOTE]  
> It's important to remember that the recorder should only 'fired' when the data is not going to change or if it makes no difference that it appears multiple times in the data aggregators.

> [!TIP]
> Example: if you're recording turnover based on an `Invoice` model you need to ensure that you fire the associated recorder only when the invoice is no longer going to be edited/changed, otherwise the turnover will be recorded multiple times and provide an incorrect value for any graphs/aggregators obtained later.


### Registering the recorder
Once you have a custom recorder you need to add it to the `recorders` array in the config file. The key is the class name of the recorder and the value is an array of configuration options. The only required option is `enabled` which is a boolean value to determine if the recorder should be used or not. The other options are optional and are passed to the recorder's constructor.

Like the standard recorders of Laravel Pulse you can pass a `sample_rate` or an array of items to `ignore`. The `sample_rate` is a number between 0 and 1 that determines the percentage of requests that should be recorded. The `ignore` option is an array of regular expressions that will be matched. If the item matches any of the regular expressions the recorder will not be used.

## `Period` enum
The `Period` enum is used to determine the intervals to use for the data. The enum is as follows:
- `Period::HOUR` - single hour interval split in 60 minute intervals
- `Period::SIXHOUR` - six hour interval split in 12 half hour intervals
- `Period::HALFDAY` - twelve hour interval split in 24 half hour intervals
- `Period::DAY` - single day interval split in 24 hour intervals
- `Period::WEEK` - standard weekly interval split in 7 day intervals
- `Period::MONTH` - standard monthly interval split in daily intervals (depending on the numnber of days in the month: `CarbonImmutable::now()->daysInMonth` is used)
- `Period::QUARTER` - standard quarterly interval split in `(int) CarbonImmutable::now()->daysInYear / 4` (~91 days) intervals
- `Period::HALFYEAR` - six-month interval split in 6-month intervals
- `Period::YEAR` - standard yearly interval split in 12-month intervals

If you want to use the `Period` enum in your own code you can import it with `use Vulpecula\Datum\Enums\Period;`. There is a `label()` function which will return the label for the enum value. For example `Period::HOUR->label()` will return `Hour`. There is a language file available that can be published using:
    
```bash
php artisan vendor:publish --tag="datum-lang"
```
Furthermore, you can get an array of all the `Period` enum values and their corresponding labels using the following:
```php
array_map(
    fn(Period $period) => $period->label(), 
    Period::cases()
);
```

This is useful if you want to provide a dropdown of the available periods for the user to select.

## Getting the data out

Once the data is in the database you'll want to display it on the dashboard (or anywhere else you want to use it).

There are two primary functions available: `graph()` and `aggregate()`.

### `graph()`
The `graph()` function will return a `Collection` of data that can be used to graph the data. The function takes 3 parameters:
- `array $types` - An array of the types of data you want to graph. The types are the `type` column of the datum tables and are set in the recorders.
- `string $aggregate` - The aggregate function to use. This can be any of the standard SQL aggregate functions: `count`, `sum`, `avg`, `min`, `max`.
- `Period $interval` - The interval to use for the data (see [above](#period-enum) for the available intervals).

```php
$graphs = \Vulpecula\Datum\Facades\Datum::graph(['user_created', 'user_deleted'], 'count', Period::DAY);
```

This will return a `Collection` of objects one for `user_created` and one for `user_deleted`. Each object will be its own collection of objects with the `key` for each object the distinct `key`s in the database that have been recorded against the `types`. The value of the collection will be (in this example) an array of 24 elements with each having the timestamp of the start of the hour and the `count` of the number of records for that hour.


### `aggregate()`
The `aggregate()` function will return a `Collection` of data that can be used to display the data in a table. The function requires 3 parameters:
- `string $type` - The type of data you want to aggregate. The type is the `type` column of the datum tables and is set in the recorders.
- `array|string $aggregates` - The aggregate function(s) to use. This can be any of the standard SQL aggregate functions: `count`, `sum`, `avg`, `min`, `max`. If you want to use multiple aggregate functions you can pass an array of the functions.
- `Period $interval` - The interval to use for the data (see [above](#period-enum) for the available intervals).

Optional parameters are:
- `?string $orderBy` - The column to order the results by. The default is `key`.
- `string $direction` - The direction to order the results by. The default is `desc`.
- `int $limit` - The number of results to return. The default is `101` which will return all results.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Richard Browne](https://github.com/rabrowne85)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
