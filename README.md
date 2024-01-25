# Datum records different data and aggregations for use on Dashboards

[![Latest Version on Packagist](https://img.shields.io/packagist/v/vulpecula-io/laravel-datum.svg?style=flat-square)](https://packagist.org/packages/vulpecula-io/laravel-datum)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/vulpecula-io/laravel-datum/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/vulpecula-io/laravel-datum/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/vulpecula-io/laravel-datum/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/vulpecula-io/laravel-datum/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/vulpecula-io/laravel-datum.svg?style=flat-square)](https://packagist.org/packages/vulpecula-io/laravel-datum)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require vulpecula-io/laravel-datum
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-datum-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-datum-config"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$datum = new Vulpecula\Datum();
echo $datum->echoPhrase('Hello, vulpecula-io!');
```

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
