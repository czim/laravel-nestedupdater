<?php

declare(strict_types=1);

namespace Czim\NestedModelUpdater;

use Czim\NestedModelUpdater\Contracts\NestedParserInterface;
use Czim\NestedModelUpdater\Contracts\NestedValidatorFactoryInterface;
use Czim\NestedModelUpdater\Contracts\NestedValidatorInterface;
use Czim\NestedModelUpdater\Data\RelationInfo;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Support\MessageBag as MessageBagContract;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\MessageBag;
use ReflectionException;
use UnexpectedValueException;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @template TParent of \Illuminate\Database\Eloquent\Model
 *
 * @extends AbstractNestedParser<TModel, TParent>
 * @implements NestedValidatorInterface<TModel, TParent>
 */
class NestedValidator extends AbstractNestedParser implements NestedValidatorInterface
{
    protected bool $validates = true;
    protected MessageBagContract $messages;


    /**
     * Performs validation and returns whether it succeeds.
     *
     * @param array<string, mixed> $data
     * @param bool                 $creating if false, validate for update
     * @return bool
     */
    public function validate(array $data, bool $creating = true): bool
    {
        $this->relationsAnalyzed = false;

        $this->validates = true;
        $this->messages  = new MessageBag();
        $this->data      = $data;
        $this->model     = null;

        $rules = $this->getValidationRules($creating);

        $validator = $this->getValidationFactory()->make($data, $rules);

        if ($validator->fails()) {
            $this->validates = false;
            $this->messages  = $validator->getMessageBag();
        }

        return $this->validates;
    }

    /**
     * Returns validation rules array for full nested data.
     *
     * @param array<string, mixed> $data
     * @param bool                 $creating
     * @return array<string, mixed>
     */
    public function validationRules(array $data, bool $creating = true): array
    {
        $this->relationsAnalyzed = false;

        $this->data = $data;

        return $this->getValidationRules($creating);
    }

    /**
     * Returns validation messages, if validation has been performed.
     *
     * @return MessageBagContract|null
     */
    public function messages(): ?MessageBagContract
    {
        if (! isset($this->messages)) {
            return null;
        }

        return $this->messages;
    }

    /**
     * Returns validation rules for the current model only
     *
     * @param bool $prefixNesting if true, prefixes the validation rules with the relevant key nesting.
     * @param bool $creating
     * @return array<string, mixed>
     */
    public function getDirectModelValidationRules(bool $prefixNesting = false, bool $creating = true): array
    {
        $rulesInstance = $this->makeModelRulesInstance();

        if (! $rulesInstance) {
            return [];
        }

        $method = $this->determineModelRulesMethod();

        if (! method_exists($rulesInstance, $method)) {
            throw new UnexpectedValueException($rulesInstance::class . " has no method '{$method}'");
        }

        $rules = $rulesInstance->{$method}($creating ? 'create' : 'update');

        if (! is_array($rules)) {
            throw new UnexpectedValueException($rulesInstance::class . "::{$method} did not return array");
        }

        if ($prefixNesting) {
            $rules = $this->prefixAllKeysInArray($rules);
        }

        return $rules;
    }

    /**
     * Returns nested validation rules for the entire nested data structure.
     *
     * @param bool $creating
     * @return array<string, mixed>
     */
    protected function getValidationRules(bool $creating = true): array
    {
        $this->config->setParentModel($this->modelClass);
        $this->analyzeNestedRelationsData();

        // Get validation rules for this model/level, prepend keys correctly with current nested key & index.
        $rules = $this->getDirectModelValidationRules(true, $creating);

        // For any child relations, created a nested validator and merge its validation rules.
        return array_merge($rules, $this->getNestedRelationValidationRules());
    }

    /**
     * @return array<string, mixed>
     */
    protected function getNestedRelationValidationRules(): array
    {
        $rules = [];

        foreach ($this->relationInfo as $attribute => $info) {
            if (! Arr::has($this->data, $attribute)) {
                continue;
            }

            if (! $info->isSingular()) {
                // Make sure we force an array if we're expecting a plural relation,
                // and make data-based rules for each item in the array.

                $rules[ $this->getNestedKeyPrefix() . $attribute ] = 'array';

                if (is_array($this->data[ $attribute ])) {
                    foreach (array_keys($this->data[ $attribute ]) as $index) {
                        $rules = array_merge(
                            $rules,
                            $this->getNestedRelationValidationRulesForSingleItem(
                                $info,
                                $attribute,
                                $index,
                            )
                        );
                    }
                }
            } else {
                $rules = array_merge(
                    $rules,
                    $this->getNestedRelationValidationRulesForSingleItem($info, $attribute)
                );
            }
        }

        return $rules;
    }

    /**
     * @param RelationInfo<Model> $info
     * @param string              $attribute key of attribute
     * @param mixed|null          $index     if data is plural for this attribute, the index for it
     * @return array<string, mixed>
     */
    protected function getNestedRelationValidationRulesForSingleItem(
        RelationInfo $info,
        string $attribute,
        mixed $index = null,
    ): array {
        $rules = [];

        $dotKey = $attribute . ($index !== null ? '.' . $index : '');

        $data = Arr::get($this->data, $dotKey);

        // If the data is scalar, it is treated as the primary key in a link-only operation, which should be allowed
        // if the relation is allowed in nesting at all -- if the data is null, it should be considered a detach
        // operation, which is allowed as well.
        if (is_scalar($data) || $data === null) {
            // Add rule if we know that the primary key should be an integer.
            if ($info->model()->getIncrementing()) {
                $isRequired = false;
                $key = $this->getNestedKeyPrefix() . $dotKey;
                $validationRules = $this->getDirectModelValidationRules();

                // check if the field is required in model validation
                if (array_key_exists($key, $validationRules)) {
                    $validationRule = $validationRules[$key];

                    if (is_string($validationRule) && preg_match('/\brequired\b/', $validationRule) !== false ||
                        in_array('required', $validationRule)
                    ) {
                        $isRequired = true;
                    }
                }

                $rules[$key] = ($isRequired ? 'required' : 'nullable') . '|integer';
            }

            return $rules;
        }

        // If not a scalar or null, the only other value allowed is an array.
        $rules[ $this->getNestedKeyPrefix() . $dotKey ] = 'array';

        $keyName       = $info->model()->getKeyName();
        $keyIsRequired = false;
        $keyMustExist  = false;

        // If it is a link-only or update-only nested relation, require a primary key field.
        // It also helps to check whether the key actually exists, to prevent problems with
        // a non-existant non-incrementing keys, which would be interpreted as a create action.
        if (! $info->isCreateAllowed()) {
            $keyIsRequired = true;
            $keyMustExist  = true;
        } elseif (! $info->model()->getIncrementing()) {
            // If create is allowed, then the primary key is only required for non-incrementing key models,
            // for which it should always be present.
            $keyIsRequired = true;
        }

        // If the primary key is not present, this is a create operation, so we must apply the model's create rules
        // otherwise, it's an update operation -- if the model is non-incrementing, however, the create/update
        // distinction depends on whether the given key exists.
        if ($info->model()->getIncrementing()) {
            $creating = ! Arr::has($data, $keyName);
        } else {
            $key      = Arr::get($data, $keyName);
            $creating = ! $key || ! $this->checkModelExistsByLookupAtribute($key, $keyName, get_class($info->model()));
        }

        if (! $creating) {
            $keyMustExist = true;
        }


        // Build up rules for primary key.
        $keyRules = [];

        if ($info->model()->getIncrementing()) {
            $keyRules[] = 'integer';
        }

        if ($keyIsRequired) {
            $keyRules[] = 'required';
        }

        if ($keyMustExist) {
            $keyRules[] = 'exists:' . $info->model()->getTable() . ',' . $keyName;
        }

        if (count($keyRules)) {
            $rules[ $this->getNestedKeyPrefix() . $dotKey . '.' . $keyName ] = $keyRules;
        }


        // Get and merge rules for model fields by deferring to a nested validator.

        /** @var NestedValidatorInterface<Model, TModel> $validator */
        $validator = $this->makeNestedParser($info->validator(), [
            $info->model()::class,
            $attribute,
            $this->appendNestedKey($attribute, $index),
            $this->model,
            $this->config,
            $this->modelClass,
        ]);

        return $this->mergeInherentRulesWithCustomModelRules($rules, $validator->validationRules($data, $creating));
    }


    /**
     * Merges validation rules intelligently, on a per-rule basis, giving preference to custom-set validation rules.
     *
     * @param array<string, mixed> $inherent
     * @param array<string, mixed> $custom
     * @return array<string, mixed>
     */
    protected function mergeInherentRulesWithCustomModelRules(array $inherent, array $custom): array
    {
        foreach ($custom as $key => $ruleSet) {
            // If it does not exist in the inherent set, add the custom rule.
            if (! array_key_exists($key, $inherent)) {
                $inherent[ $key ] = $ruleSet;
                continue;
            }

            // Otherwise: normalize and merge the rules, removing duplicates.
            $mergedRules = array_merge(
                $this->normalizeRulesForKeyAsArray($inherent[ $key ]),
                $this->normalizeRulesForKeyAsArray($ruleSet)
            );

            $inherent[ $key ] = array_unique($mergedRules);
        }

        // Return inherent set, which now has custom rules merged in.
        return $inherent;
    }

    /**
     * Normalizes ruleset for a single attribute key to an array of strings.
     *
     * @param string|string[] $rules
     * @return string[]
     */
    protected function normalizeRulesForKeyAsArray(string|array $rules): array
    {
        if (! is_array($rules)) {
            $rules = explode('|', $rules);
        }

        return $rules;
    }

    protected function getNestedKeyPrefix(): string
    {
        return $this->nestedKey ? $this->nestedKey . '.' : '';
    }

    /**
     * Prefixes all keys in an associative array with a string.
     *
     * @param array<string, mixed> $array
     * @param null|string          $prefix if not given, prefixes with nesting prefix for this validator level
     * @return array<string, mixed>
     */
    protected function prefixAllKeysInArray(array $array, ?string $prefix = null): array
    {
        $prefix = $prefix ?? $this->getNestedKeyPrefix();

        return array_combine(
            array_map(
                static fn ($key) => $prefix . $key,
                array_keys($array)
            ),
            array_values($array)
        );
    }

    /**
     * Returns FQN of rules class.
     *
     * @return class-string
     */
    protected function determineModelRulesClass(): string
    {
        $rulesClass = $this->parentRelationInfo ? $this->parentRelationInfo->rulesClass() : null;

        // Default: use per-model class.
        if (! $rulesClass) {
            $modelConfig = Config::get('nestedmodelupdater.validation.model-rules.' . $this->modelClass);

            if (is_array($modelConfig)) {
                $rulesClass = Arr::get($modelConfig, 'class');
            } else {
                $rulesClass = $modelConfig;
            }
        }

        // fallback: use namespace & postfix, using model's basename
        if (! $rulesClass) {
            $namespace = Config::get('nestedmodelupdater.validation.model-rules-namespace');
            $postFix   = Config::get('nestedmodelupdater.validation.model-rules-postfix');

            $rulesClass = rtrim($namespace, '\\') . '\\' . class_basename($this->modelClass) . $postFix;
        }

        return $rulesClass;
    }

    /**
     * Returns method for rules on the rules class.
     *
     * @return string
     */
    protected function determineModelRulesMethod(): string
    {
        $rulesMethod = $this->parentRelationInfo ? $this->parentRelationInfo->rulesMethod() : null;

        // use per-model method, if defined
        if (! $rulesMethod) {
            $modelConfig = Config::get('nestedmodelupdater.validation.model-rules.' . $this->modelClass);

            if (is_array($modelConfig)) {
                $rulesMethod = Arr::get($modelConfig, 'method');
            }
        }

        return $rulesMethod ?: Config::get('nestedmodelupdater.validation.model-rules-method', 'rules');
    }

    /**
     * Makes instance of class that should contain the rules method.
     *
     * @return object|false
     */
    protected function makeModelRulesInstance(): object|false
    {
        $class = $this->determineModelRulesClass();

        try {
            $instance = app($class);
        } catch (BindingResolutionException|ReflectionException $e) {
            $instance = null;
        }

        if ($instance === null) {
            if (! Config::get('nestedmodelupdater.validation.allow-missing-rules', true)) {
                throw new UnexpectedValueException("{$class} is not bound as a usable rules object");
            }

            return false;
        }

        if (! is_object($instance)) {
            throw new UnexpectedValueException("{$class} is not a usable rules object");
        }

        return $instance;
    }

    /**
     * {@inheritdoc}
     * @return NestedValidatorInterface<Model, TModel>
     */
    protected function makeNestedParser(string $class, array $parameters): NestedParserInterface
    {
        return $this->getNestedValidatorFactory()->make($class, $parameters);
    }

    protected function getValidationFactory(): Factory
    {
        return app(Factory::class);
    }

    /**
     * @return NestedValidatorFactoryInterface<Model, TModel>
     */
    protected function getNestedValidatorFactory(): NestedValidatorFactoryInterface
    {
        return app(NestedValidatorFactoryInterface::class);
    }
}
