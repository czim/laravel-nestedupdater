<?php

return [

    // Enable database transactions for top-level create/update operations.
    'database-transactions' => true,
    
    // List of FQNs of relation classes that are of the to One type. Every
    // other relation is considered plural.
    'singular-relations' => [
        \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
        \Illuminate\Database\Eloquent\Relations\HasOne::class,
        \Illuminate\Database\Eloquent\Relations\MorphOne::class,
        \Illuminate\Database\Eloquent\Relations\MorphTo::class,

        '\Znck\Eloquent\Relations\BelongsToThrough',
    ],

    // List of FQNs of relation classes that have their ids stored as
    // foreign keys on the parent class of the relation. Any nested update
    // operation on one of these will be performed before updating or
    // creating the parent model.
    'belongs-to-relations' => [
        \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
        \Illuminate\Database\Eloquent\Relations\MorphTo::class,
    ],

    // Definitions for nested updatable relations, per parent model FQN as key.
    // Each definition should be keyed by its attribute key (as it would be set in
    // an update data array (typically snake cased).
    //
    // This may store:
    //      link-only       boolean     true if we're not allowed to update through nesting (default: false)
    //      detach-empty    boolean     true if providing empty data will detach/dissociate (default: true)
    //      updater         string      FQN of ModelUpdaterInterface class that should handle things
    //      method          string      method name for the relation, if not camelcased attribute key
    //      rules           ...
    //
    //          'Some\Model\Class' => [
    //              'relation_key' => [ 'link-only' => true, 'updater' => 'Some\Updater\Class' ]
    //          ]
    //
    // Alternatively, set the key's value to boolean true to use defaults and allow full updates.
    //
    //          'Some\Model\Class\' => [ 'relation_key' => true ]
    //
    // If a relation is not present in this config, no nested updating or linking will
    // allowed at all.
    //
    'relations' => [
    ],

];
