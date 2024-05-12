# Laravel Translation Dumper

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bambamboole/laravel-translation-dumper.svg?style=flat-square)](https://packagist.org/packages/bambamboole/laravel-translation-dumper)
[![Total Downloads](https://img.shields.io/packagist/dt/bambamboole/laravel-translation-dumper.svg?style=flat-square)](https://packagist.org/packages/bambamboole/laravel-translation-dumper)
![GitHub Actions](https://github.com/bambamboole/laravel-translation-dumper/actions/workflows/main.yml/badge.svg)
[![codecov](https://codecov.io/gh/bambamboole/laravel-translation-dumper/branch/main/graph/badge.svg?token=PTTDMH5DSJ)](https://codecov.io/gh/bambamboole/laravel-translation-dumper)

This package provides an option to extend Laravels Translator to write missing 
translation keys to their respective translation files.  
This is especially useful if you have dynamic translation keys where static dumpers do not work.

## Installation

You can install the package via composer.
Since this package should only be used in development, you should add the `--dev` flag.

```bash
composer require --dev bambamboole/laravel-translation-dumper
```

## Usage

```php
To enable the dumper, you have to set the environment variable `DUMP_TRANSLATIONS` to `true`.
```

## Workflow
Just write your templates and use the `__()` helper as usual with your preferred 
translation key pattern. As soon as you visit the page, the missing translation keys,
they will be written to the respective translation files in the structure that the first 
part of the key is the file name and the rest is the nested path of a PHP array.
As value it takes the translation key itself prefixed by a configurable prefix.

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

