# Configuration

## Database Transactions

When a nested model update process is started, it is, by default, run in a database transaction.
This means that if any exception is thrown, all changes will be rolled back. If you do not want
this to happen, unset the `database-transactions` option in the configuration file, or call
`disableDatabaseTransactions()` on the model updater before running the process.


## Relations Configuration

Each relation may have a configuration section array in which specific options may be set for updating the relations with the model updater.
If `true` is used instead of an array, default options are used.

```php
<?php
    // The model class:
    App\Models\Post::class => [
        'comments' => [
            // options defined here ...
        ]
    ],
```

Note that 'comments' in the above example refers to the key in the array that contains the nested data, *not* the relation method name. See the `method` option in the list below.

## Relation options

The options that may be be set are as follows:

- `link-only` (boolean): 
    Enable this to only allow (re-)linking nested models, but not updating them or creating new (default is `false`)
- `update-only` (boolean):
    Enable this to only allow updating existing models through nesting, but not creating new (default is `false`)
- `detach` (boolean):
    Set this to `false` or `true` to control whether records omitted from a set of nested records for the relation
    are detached from their parent model. If this is `true`, detaching is forced. 
    (default is `null`, which defaults to `true` for `BelongsToMany` and `false` for `HasMany` type relations)
- `delete-detached` (boolean):
    If this is enabled, models that are omitted (and would be detached if `detach` is enabled), will be deleted instead.
    There is a simple check in place to prevent models that are still 'in use' are not deleted, but use at your own risk!
    (default is `false`)
- `method` (string):
    By default, the relation method called on the model is the attribute key for the relation, camelCased.
    If the relation method does not follow this pattern, define the method with this option.
    (default is `null`)
- `updater` (string):
    If you want your own implementation of the `ModelUpdaterInterface` to handle nested update or create actions for
    the relation, you can set the fully qualified namespace for it here.
    (default is `null`, uses the default package `ModelUpdater` class)

And for validation:

- `validator` (string):
    If you want your own implementation of the `NestedUpdaterInterface` to handle nested validation for
    the relation, you can set the fully qualified namespace for it here.
    (default is `null`, uses the default package `NestedValidator` class.
- `rules` (string):
    If you want to override the default validation rules class (see validation configuration options) for
    the relation, you can set a fully qualified namespace for a class here.
    (default is `null`)
- `rules-method` (string):
    If you want to override the default validation rules method to be called on the rules class
    (see validation configuration options) for the relation, you can set the method here.
    (default is `rules`)


## Validation configuration

The above relations options for validation overrule the validator defaults. 
The validation defaults are configured in the `nestedmodelupdater.validation` section of the config.


### Rules class fallback

The default fallback for rules classes ([see the readme section on validation](VALIDATON.md)) works as follows:
Given a model, say `App\Models\Post`, the class name will be constructed as follows:

    model-rules-namespace + basename of model class + optional postfix
    
For example:
    
    App\Http\\Requests\Rules\ + Post + Rules = App\Http\\Requests\Rules\PostRules 
 
The namespace and postfix may be configured in the `valiation` section:

```php
<?php
    'model-rules-namespace' => 'App\\Http\\Requests\\Rules',
    'model-rules-postfix'   => 'Rules',
```

Note that using this fallback option is entirely optional. 
`model-rules` and/or `relations` settings may be used to prevent the fallback from ever being used. 


### Allowing missing rules classes

By default, if a rules class fallback is not found or instantiable, an empty set of rules is silently used.
This behavior may be altered by chaning the value for `validation.allow-missing-rules`:

```php
<?php
    'allow-missing-rules' => false,
```

When set to false, this will cause an `UnexpectedValueException` to be thrown if no rules class is available.
Note that exceptions will always be thrown if a class is available, but the *method* is not, or cannot be used.


### Rules method

The indicated class will be instantiated, and a call to the `rules()` method will be performed on it.
This default method may be changed:

```php
<?php
    // This would default to calling customMethod() instead of rules()
    'model-rules-method' => 'customMethod',
``` 
    
### Model Rules

It is also possible to set rules classes and methods on a per-model basis, in the `validation.model-rules` array.
These will apply for any validation of the model's data, regardless of its nested relation context.

```php
<?php

    'model-rules' => [
    
        // If a string value is used, it should be the rules class FQN
        // the default rules method would be used in this case.
        App\Models\Post::class => Your\RulesClass::class,
        
        // If a rules method needs to be defined, use an array for the
        // value and set it as follows. Note that 'class' and 'method'
        // are both optional; the default/fallback will be used for any
        // option not specified.
        App\Models\Comment::class => [
            'class'  => Your\RulesClass::class,
            'method' => 'rulesForComment',
        ],
    ],
```

Note that the model-specific settings are overruled by relation-specific rules settings.


### A note on detaching

If the `detach` option is enabled, `BelongsToMany` relations will be synced with detaching enabled.

When detaching `HasMany` or `HasOne` relations, the foreign keys for the detached models will be set to `NULL`.
This will of course only work for records that have nullable foreign keys. If that is not the case, the operation will fail with a generic SQL error.
In that case you may decide to use the `delete-detached` option, so the 'detached' records will be deleted instead.


### Extending the `ModelUpdater`

A note on the data returned by `handleNestedSingleUpdateOrCreate()`:
This will normally always return an instance of `UpdateResult` with a model set (the model updated or created).
However, if the result has no model set (`null`), this is a valid result, and the updater will not fail when this happens.
This is done so that, optionally, in an extension of the updater, models may conditionally not be created, or deleted. 
