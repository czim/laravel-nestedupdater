# Laravel Nested Model Updater

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status](https://travis-ci.org/czim/laravel-nestedupdater.svg?branch=master)](https://travis-ci.org/czim/laravel-nestedupdater)
[![Latest Stable Version](http://img.shields.io/packagist/v/czim/laravel-nestedupdater.svg)](https://packagist.org/packages/czim/laravel-nestedupdater)

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

Note that this package will not do any nested updates without setting up at least a
configuration for the relations that you want to allow nested updates for. See the 
configuration section below.


## Configuration

In the `nestedmodelupdater.php` config, configure your relations per model under the `relations` key.
Add keys of the fully qualified namespace of each model that you want to allow nested updates for.
Under each, add keys for the attribute names that you want your nested structure to have for each relation's data. 
Finally, for each of those, either add `true` to enable nested updates with all default settings, or override settings in an array.

As a simple example, if you wish to add comments when creating a post, your setup might look like the following.

The updating data would be, something like this:

```php
$data = [
    'title' => 'new post title',
    'comments' => [
        [
            'body' => 'new comment body text',
            'author' => $existingAuthorId
        ],
        $existingCommentId
    ]
],
```

This could be used to update a post (or create a new post) with a title, create a new comment (which is linked to an existing author) and link an existing comment, and link both to the post model.

The `relations`-configuration to make this work would look like this:

```php
'relations' => [
    // The model class:
    App\Models\Post::class => [
        // the data nested relation attribute
        // with a value of true to allow updates with default settings
        'comments' => true
    ],
    
    App\Models\Comment::class => [
        // this time, the defaults are overruled to only allow linking,
        // not direct updates of authors through nesting
        'author' => [
            'link-only' => true
        ]
    ]
],
```

Note that any relation not present in the config will be ignored for nesting, and passed as fill data into the main model on which the create or update action is performed.

More [information on relation configuration](CONFIG.md). 
Also check out [the configuration file](https://github.com/czim/laravel-nestedupdater/blob/master/src/config/nestedmodelupdater.php) for further notes.



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
