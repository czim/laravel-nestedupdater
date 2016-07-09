# Validation

## Manually setting up the validator

To Do: add an example here

Setting up a validator is very much like using the `ModelUpdater`:

```php
<?php
    // Instantiate the validator
    $validator = new \Czim\NestedModelUpdater\NestedValidator(YourModel::class);
    
    // Or by using the service container binding
    $validator = app(\Czim\NestedModelUpdater\Contracts\NestedValidatorInterface::class, [ YourModel::class ]);
    
    // Perform validation for create
    $model = $validator->validate([ 'some' => 'create', 'data' => 'here' ], true);
    
    // or update
    $model = $validator->validate([ 'some' => 'create', 'data' => 'here' ], false);
    
```

### Retrieving validation rules

Alternatively, it is possible to extract the validation rules without performing validation directly:

```php
<?php
    // Instantiate the validator
    $validator = new \Czim\NestedModelUpdater\NestedValidator(YourModel::class);
    
    // Perform validation for create
    $model = $validator->validationRules([ 'some' => 'create', 'data' => 'here' ], true);
    
    // or update
    $model = $validator->validationRules([ 'some' => 'create', 'data' => 'here' ], false);

```

## Form Requests

The plan here is to set up a standardized Request that would take
a model class FQN for configuration and detect create/update actions
(with some level of configurability).


## Automatic validation when updating or creating models

Currently considering whether this should be added as an option.
The idea would be to hook into the model's `saving` event by using a trait,
which would fire up a validator process.
