<?php

return [

    // Enable database transactions for top-level create/update operations.
    'database-transactions' => true,

    // Allows using temporary ids to refer to records that are not created yet.
    'allow-temporary-ids' => false,

    // If allowed, the key to look for temporary create ids to look for
    'temporary-id-key' => '_tmp_id',
    
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
    //
    //      link-only       boolean     true if we're not allowed to update through nesting (default: false).
    //      update-only     boolean     true if we're not allowed to create through nesting (default: false).
    //      updater         string      FQN of ModelUpdaterInterface class that should handle things.
    //      method          string      method name for the relation, if not camelcased attribute key.
    //      detach          boolean     if true, performs detaching sync for BelongsToMany, dissociates
    //                                  children in HasMany, relations not present in the update data.
    //                                  (default: true for BelongsToMany, false for HasMany)
    //      delete-detached boolean     if true, deletes instead of detaching. for HasMany relations this
    //                                  means that instead of setting the foreign key NULL, for BelongsToMany
    //                                  related models are deleted if they are not related to anything else
    //                                  (default: false).
    //      validator       string      FQN of NestedValidatorInterface that should handle validation.
    //      rules           string      FQN of the class that provides the rules for the model
    //      rules-method    string      method name on the rules class to use (default: 'rules')
    //
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
