# Eloquent Nested Model Updater for Laravel

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status](https://travis-ci.org/czim/laravel-nestedupdater.svg?branch=master)](https://travis-ci.org/czim/laravel-nestedupdater)
[![Latest Stable Version](http://img.shields.io/packagist/v/czim/laravel-nestedupdater.svg)](https://packagist.org/packages/czim/laravel-nestedupdater)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/cb05e233-afac-486e-b276-2765d1461cd6/mini.png)](https://insight.sensiolabs.com/projects/cb05e233-afac-486e-b276-2765d1461cd6)

Package for updating nested eloquent model relations using a single data array.

This package will make it easy to create or update a group of nested, related models through a single method call.
For example, when passing in the following data for an update of a Post model ...

```php
<?php

$data = [
    'title' => 'updated title',
    'comments' => [
        17,
        [
            'id' => 18,
            'body' => 'updated comment body',
            'author' => [
                'name' => 'John',
            ],
        ],
        [
            'body' => 'totally new comment',
            'author' => 512,
        ],
    ],
];

```

... this would set a new title for Post model being updated, but additionally:

- link comment #17 to the post,
- link and/or update comment #18 to the post, setting a new body text for the comment,
- create a new author named 'John' and linking it to comment #18,
- create a new comment for the post and linking author #512 to it

Any combination of nested creates and updates is supported; the nesting logic follows that of Eloquent relationships and is highly customizable. 

Additionally, this package provides support for validating data with nested relations all at once. 


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
configuration for the relations that you want to allow nested updates for. 
Configuration must be set before this can be used at all. See the configuration section below.


### NestedUpdatable Trait

An easy way to set up a model for processing nested updates is by using the `NestedUpdatable` trait:

```php
<?php
class YourModel extends Model
{
    use \Czim\NestedModelUpdater\Traits\NestedUpdatable;
    
    // ...
```

Any data array passed into `create()` or `update()` calls for that model will be processed for nested data. 
Note that `fill()` (or any other data-related methods on the model) will *not* be affected, and do not process nested data with the model updater.

If you wish to use your own implementation of the `ModelUpdaterInterface`, you may do so by setting a (protected) property `$modelUpdaterClass` with the fully qualitied namespace for the updater.
This is entirely optional and merely availble for flexibility.

```php
<?php
class YourCustomizedModel extends Model
{
    use \Czim\NestedModelUpdater\Traits\NestedUpdatable;
    
    /**
     * You can refer to any class, as long as it implements the
     * \Czim\NestedModelUpdater\Contracts\ModelUpdaterInterface.
     *
     * @var string
     */
    protected $modelUpdaterClass = \Your\UpdaterClass\Here::class;
    
    /**
     * Additionally, optionally, you can set a class to be used
     * for the configuration, if you need to override how relation
     * configuration is determined.
     *
     * This class must implement
     * \Czim\NestedModelUpdater\Contracts\NestingConfigurationInterface
     * 
     * @var string
     */
    protected $modelUpdaterConfigClass = \Your\UpdaterConfigClass::class;
    
```


### Manual ModelUpdater Usage

Alternatively, you can use the `ModelUpdater` manually, by creating an instance.

```php
<?php

    // Instantiate the modelupdater
    $updater = new \Czim\NestedModelUpdater\ModelUpdater(YourModel::class);
    
    // Or by using the service container binding
    $updater = app(\Czim\NestedModelUpdater\Contracts\ModelUpdaterInterface::class, [ YourModel::class ]);
    
    // Perform a nested data create operation
    $model = $updater->create([ 'some' => 'create', 'data' => 'here' ]);
    
    // Perform a nested data update on an existing model
    $updater->update([ 'some' => 'update', 'data' => 'here' ], $model);
    
```


## Configuration

In the `nestedmodelupdater.php` config, configure your relations per model under the `relations` key.
Add keys of the fully qualified namespace of each model that you want to allow nested updates for.
Under each, add keys for the attribute names that you want your nested structure to have for each relation's data. 
Finally, for each of those, either add `true` to enable nested updates with all default settings, or override settings in an array.

As a simple example, if you wish to add comments when creating a post, your setup might look like the following.

The updating data would be something like this:

```php
<?php
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
<?php
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


## Validation

Validation is not automatically performed by the model updater. This package offers nested validation as a separate process,
that may be implemented as freely as that of the updater itself. 
A `NestedValidator` class may be used to perform validation or return validation rules based on the data provided and the
relations configuration set. This will reflect update- or link-only rights and rules for records existing on using primary
keys when updating.

[Further information on setting up validation here](VALIDATION.md), including different approaches for intiating validation.



## Non-incrementing primary keys

The behavior for dealing with models that have non-incrementing primary keys is slightly different.
Normally, the presence of a primary key attribute in a data set will make the model updater assume that an existing record needs to be linked or updated, and it will throw an exception if it cannot find the model. Instead, for non-incrementing keys, it is assumed that any key that does not already exist is to be added to the database.

If you do not want this, you will have to filter out these occurrences before passing in data to the updater,
or make your own configuration option to make this an optional setting.


## Temporary IDs: Referencing to-be created models in nested data

When creating models through nested updates, it may be necessary to create a single new model once, 
but link it to multiple other parents. Take the following data example:

```php
<?php

$data = [
    'title' => 'New post title',
    'comments' => [
        [
            'body' => 'Some comment',
            'author' => [
                'name' => 'Howard Hawks'
            ]
        ],
        [
            'body' => 'Another comment',
            'author' => [
                'name' => 'Howard Hawks'
            ]
        ]
    ]
];
```

The above data would create two new authors with the same name, which is likely undesirable.
If only one new author should be created, and connected to both comments, this may be done using
temporary IDs:

```php
<?php

$data = [
    'title' => 'New post title',
    'comments' => [
        [
            'body' => 'Some comment',
            'author' => [
                '_tmp_id' => 1,
                'name' => 'Howard Hawks'
            ]
        ],
        [
            'body' => 'Another comment',
            'author' => [
                '_tmp_id' => 1,
            ]
        ]
    ]
];
```

This would create a single new author with the given name and connect it to both comments. 

The `_tmp_id` reference must be unique for one to-be created model.
It may be an integer or a string value, but it *must not* contain a period (`.`).

Because there is a (minor) performance cost to checking for temporary IDs, this is disabled by default.
To enable it, simply set `allow-temporary-ids` to `true` in the configuration.

There are no deep checks for cyclical references or (fairly unlikely) dependency issues for multiple interrelated temporary ID create operations, so be careful with this or perform in-depth validation manually beforehand.

 
## Unguarded Attributes

Updates and creates adhere to the `fillable` guards for the relevant models.

It is possible to prepare the model updater to set attributes bypassing the guard.
This may be done using the `setUnguardedAttribute()` method on the model updater, before calling `update()` or `create()`.

Example:

```php
<?php
    // Instantiate the modelupdater
    $updater = new \Czim\NestedModelUpdater\ModelUpdater(YourModel::class);
    
    // Queue an non-fillable attribute to be stored on the create model
    $updater->setUnguardedAttribute('user_id', \Auth::user()->id);
    
    // Perform a nested data create operation
    $model = $updater->create([ 'some' => 'create', 'data' => 'here' ]);
```

In this case the `user_id` would be stored directly on the newly created model.

As a safety measure, any previously set unguarded attributes will be cleared automatically after a succesful model update or create operation.
Set them again for each subsequent update/create to be performed.

It is also possible to set an entire array of unguarded attributes to assign at once:

```php
<?php
    $updater->setUnguardedAttributes([
        'some_attribute' => 'example',
        'another'        => 'value',
    ]);
```

Currently queued unguarded attributes to be assigned may be retrieved using `getUnguardedAttributes()`.
The unguarded attributes may also be cleared at any time using `clearUnguardedAttributes()`.


## Associative array data

Be careful submitting associative arrays for entries of plural relations.
They are supported, but can easily break validation:
Problems will arise when data is submitted with associative keys that contain `'.'` like so:

```php
<?php
$data = [
    'comments' => [
        'some.key' => [
            'body' => 'Some comment',
        ],
        'another-key' => [
            'body' => 'Another comment',
        ]
    ]
];
```

Since Laravel's `Arr::get()` and other dot-notation based lookup methods are used,
nested validation will fail to properly validate data entries with such keys.  

Note that such associative keys serve no purpose for the model updater itself.
The best way to avoid problems is to normalize your data so that all plural relation arrays are non-associative.
Alternatively, replace any `.` in the array's keys with a placeholder. 
See 'Extending functionality' below for tips.


## Extending functionality

The `ModelUpdater` class should be considered a prime candidate for customization.
The `normalizeData()` method may be overridden to manipulate the data array passed in before it is parsed.
Additionally check out `deleteFormerlyRelatedModel()`, which may be useful to set up in cases where conditions for deleting need to be refined.

Note that it is your own ModelUpdater extension may be set for specific relations by using the `updater` attribute.

The validator shares much of the updater's structure, so it should be equally easy to extend.


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
