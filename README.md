# Laravel Nested Model Updater

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)


Package for allowing updating of nested eloquent model relations using a single data array.

## To Do

- Validation setup, rules 'framework'
- Config documentation, test all configurables
- Test with non-standard primary key attribute names


## Install

Via Composer

``` bash
$ composer require czim/laravel-nestedupdater
```

Add this line of code to the providers array located in your `config/app.php` file:

```php
    Czim\NestedModelUpdater\NestedModelUpdaterServiceProvider::class,
```

Publish the configuration:

``` bash
$ php artisan vendor:publish
```

## Usage



## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## Credits

- [Coen Zimmerman][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/czim/laravel-nestedupdater.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/czim/laravel-nestedupdater.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/czim/laravel-nestedupdater
[link-downloads]: https://packagist.org/packages/czim/laravel-nestedupdater
[link-author]: https://github.com/czim
[link-contributors]: ../../contributors
