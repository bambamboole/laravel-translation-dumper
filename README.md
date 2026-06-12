# Laravel Translation Dumper

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bambamboole/laravel-translation-dumper.svg?style=flat-square)](https://packagist.org/packages/bambamboole/laravel-translation-dumper)
[![Total Downloads](https://img.shields.io/packagist/dt/bambamboole/laravel-translation-dumper.svg?style=flat-square)](https://packagist.org/packages/bambamboole/laravel-translation-dumper)
![GitHub Actions](https://github.com/bambamboole/laravel-translation-dumper/actions/workflows/ci.yml/badge.svg)
[![codecov](https://codecov.io/gh/bambamboole/laravel-translation-dumper/branch/main/graph/badge.svg?token=PTTDMH5DSJ)](https://codecov.io/gh/bambamboole/laravel-translation-dumper)

This package provides an option to extend Laravels Translator to write missing 
translation keys to their respective translation files.  
This is especially useful if you have dynamic translation keys where static dumpers do not work.

## Requirements

- PHP 8.2+
- Laravel 11, 12 or 13

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

By default a dotted key goes into a flat top level file (`a.b.c` → `a.php`), unless a
matching nested file already exists (`a/b.php`), in which case the key is written there.

## Writing translations

Persistence is handled behind the `TranslationWriter` interface, and the package ships
a `FileTranslationWriter` that reads, merges and writes the lang files (PHP via
`brick/varexporter`, JSON with unescaped unicode/slashes, both with an exclusive lock).
Bind your own implementation to `TranslationWriter::class` to persist elsewhere.

```php
use Bambamboole\LaravelTranslationDumper\FileTranslationWriter;
use Bambamboole\LaravelTranslationDumper\TranslationDumper;

$dumper = new TranslationDumper(new FileTranslationWriter($filesystem, lang_path()), 'en');
```

### Dumping into a specific (nested) file

Pass a `group` to write every translation into one file, **creating it if needed** —
the group is the path under the locale directory, so it can be nested:

```php
$dumper->dump([
    new Translation('subtitle', 'x-subtitle'),
    new Translation('status.open', 'x-status.open'),
], 'entities/salesOrder');
// -> lang/en/entities/salesOrder.php  (created), keys relative to the group
```

This pairs with i18next namespaces (`entities/salesOrder`), where the namespace is the
group path: missing keys round-trip straight back into the matching nested file.

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

