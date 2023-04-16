# Very short description of the package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bambamboole/laravel-translation-dumper.svg?style=flat-square)](https://packagist.org/packages/bambamboole/laravel-translation-dumper)
[![Total Downloads](https://img.shields.io/packagist/dt/bambamboole/laravel-translation-dumper.svg?style=flat-square)](https://packagist.org/packages/bambamboole/laravel-translation-dumper)
![GitHub Actions](https://github.com/bambamboole/laravel-translation-dumper/actions/workflows/main.yml/badge.svg)

This package provides an option to extend Laravels Translator to write missing 
dotted translation keys to their respective translation files.  
This is especially useful if you have dynamic translation keys where static dumpers do not work.

## Installation

You can install the package via composer:

```bash
composer require bambamboole/laravel-translation-dumper
```

## Usage

```php
To enable the dumper, you have to set the environment variable `DUMP_TRANSLATIONS` to `true`.
```

### Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email manuel@christlieb.eu instead of using the issue tracker.

## Credits

-   [Manuel Christlieb](https://github.com/bambamboole)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

