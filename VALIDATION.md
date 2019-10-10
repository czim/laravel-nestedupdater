# Validation

## Model rules

The validator will automatically determine rules for validating primary keys and the general nested structure.
Specific rules for models must be provided separately, through classes with a `rules()` method that return
rules for that model. 

The [nested model updater configuration](CONFIG.md) may be used to set a default namespace and naming scheme
to look for classes that contain rules, or specific rules classes may be defined for specific models, or for
specific nested relations.

However it is set up, the end result should be a model that may be instantiated and provide a method like
this:

```php
<?php
    public function rules()
    {
        return [
            'name' => 'string|max:50'
        ];
    }
```

The `rules` method may optionally use a `$type` parameter, to differentiate between update and create
validation rules:

```php
<?php
    public function rules($type = 'create')
    {
        if ($type === 'create') {
            return [
                'name' => 'required|string|max:50'
            ];
        } else {
            return [
                'name' => 'string'
            ];
        }
    }
```

Currently type will always be either `'create'` or `'update'`, and reflects the nested relation action
that would be performed by processing the nested data structure with the model updater.


## Manually setting up the validator

To Do: add an example here

Setting up a validator is very much like using the `ModelUpdater`:

```php
<?php
    // Instantiate the validator
    $validator = new \Czim\NestedModelUpdater\NestedValidator(YourModel::class);
    
    // Or by using the service container binding (won't work in Laravel 5.4)
    $validator = app(\Czim\NestedModelUpdater\Contracts\NestedValidatorInterface::class, [ YourModel::class ]);
    
    // Perform validation for create
    $success = $validator->validate([ 'some' => 'create', 'data' => 'here' ], true);
    
    // or update
    $success = $validator->validate([ 'some' => 'create', 'data' => 'here' ], false);
    
    // If validation fails, the error messages may be retrieved.
    // If validation succeeds, the messages() response will always be an empty MessageBag instance.
    if ( ! $success) {
        $errors = $validator->messages();
        
        dd($errors);
    } 
```


### Retrieving validation rules

Alternatively, it is possible to extract the validation rules without performing validation directly:

```php
<?php
    // Instantiate the validator
    $validator = new \Czim\NestedModelUpdater\NestedValidator(YourModel::class);
    
    // Perform validation for create
    $rules = $validator->validationRules([ 'some' => 'create', 'data' => 'here' ], true);
    
    // or update
    $rules = $validator->validationRules([ 'some' => 'create', 'data' => 'here' ], false);
```

The rules are returned as a flat associative array.


## Form Requests

An abstract form request class is provided to make it easier to set up custom nested data
form requests. To use it, extend `Czim\NestedModelUpdater\Requests\AbstractNestedDataRequest`:

```php
<?php
    namespace App\Http\Requests;

    use Czim\NestedModelUpdater\Requests\AbstractNestedDataRequest;

    class YourNestedDataRequest extends AbstractNestedDataRequest
    {
    
        protected function getNestedModelClass(): string
        {
            return \App\Model\YourModel::class;
        }
    
        protected function isCreating(): bool
        {
            // As an example, the difference between creating and updating here is
            // simulated as that of the difference between using a POST and PUT method.
    
            return request()->getMethod() != 'PUT' && request()->getMethod() != 'PATCH';
        }
    
    }
```

All the usual rules for using Form Requests apply, including the `authorize()` method and
the redirection behaviour for failed validation.
