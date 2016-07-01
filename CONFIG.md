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
    (default is `null`, uses the default package `ModelUpdater` class.


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
