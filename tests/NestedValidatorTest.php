<?php

declare(strict_types=1);

namespace Czim\NestedModelUpdater\Test;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Czim\NestedModelUpdater\NestedValidator;
use Czim\NestedModelUpdater\Test\Helpers\Models\Genre;
use Czim\NestedModelUpdater\Test\Helpers\Models\Post;
use Czim\NestedModelUpdater\Test\Helpers\Models\Special;
use Czim\NestedModelUpdater\Test\Helpers\Models\Tag;
use UnexpectedValueException;

class NestedValidatorTest extends TestCase
{
    /**
     * @test
     */
    public function it_performs_validation_on_a_nested_data_set_without_errors(): void
    {
        $data = [
            'title' => 'allowed title',
            'genre' => [
                'name' => 'allowed genre',
            ],
        ];

        $validator = new NestedValidator(Post::class);

        static::assertTrue($validator->validate($data), 'Validation should succeed');
        static::assertTrue($validator->messages()->isEmpty(), 'Validation messages should be empty');
    }

    /**
     * @test
     */
    public function it_performs_validation_on_a_nested_data_set_with_errors(): void
    {
        $this->createGenre('existing genre name');

        $data = [
            'title' => 'disallowed title that is way and way too long to be allowed '
                     . 'by the nested validator',
            'genre' => [
                'name' => 'existing genre name',
            ],
        ];

        $validator = new NestedValidator(Post::class);

        static::assertFalse($validator->validate($data), 'Validation should fail');

        $messages = $validator->messages();

        $this->assertHasValidationErrorLike($messages, 'title', '50 characters');
        $this->assertHasValidationErrorRegex($messages, 'genre.name', '(unique|been taken)');
    }

    /**
     * @test
     */
    public function it_performs_validation_on_a_deeply_nested_data_set_with_errors(): void
    {
        $data = [
            'title' => 'disallowed title that is way and way too long to be allowed '
                     . 'by the nested validator',
            'comments' => [
                [
                    'title'  => 12,
                    'author' => [
                        'name' => 13
                    ]
                ],
                [
                    'id'    => 999,
                    'title' => 'updated comment title',
                ],
                'erroneous string'
            ],
            'genre' => [
                'name' => 'allowed genre',
            ],
            'tags' => 'not an array',
        ];

        $validator = new NestedValidator(Post::class);

        static::assertFalse($validator->validate($data), 'Validation should fail');

        $messages = $validator->messages();

        $this->assertHasValidationErrorLike($messages, 'title', '50 characters');
        $this->assertHasValidationErrorLike($messages, 'comments.0.title', 'string');
        $this->assertHasValidationErrorLike($messages, 'comments.0.body', 'required');
        $this->assertHasValidationErrorLike($messages, 'comments.0.author.name', 'string');
        $this->assertHasValidationErrorLike($messages, 'comments.1.id', 'invalid');
        $this->assertHasValidationErrorLike($messages, 'comments.1.body', 'required');
        $this->assertHasValidationErrorLike($messages, 'comments.2', 'integer');
        $this->assertHasValidationErrorLike($messages, 'tags', 'array');

        static::assertCount(8, $messages);
    }

    /**
     * @test
     */
    public function it_performs_validation_correctly_for_associative_plural_relation_array(): void
    {
        $data = [
            'title' => 'required',
            'comments' => [
                'test' => [
                    'title'  => 12,
                    'author' => [
                        'name' => 13
                    ]
                ],
                3948 => [
                    'id'    => 999,
                    'title' => 'updated comment title',
                ]
            ]
        ];

        $validator = new NestedValidator(Post::class);

        static::assertFalse($validator->validate($data), 'Validation should fail');

        $messages = $validator->messages();

        $this->assertHasValidationErrorLike($messages, 'comments.test.title', 'string');
        $this->assertHasValidationErrorLike($messages, 'comments.test.body', 'required');
        $this->assertHasValidationErrorLike($messages, 'comments.test.author.name', 'string');
        $this->assertHasValidationErrorLike($messages, 'comments.3948.id', 'invalid');
        $this->assertHasValidationErrorLike($messages, 'comments.3948.body', 'required');

        static::assertCount(5, $messages);
    }


    // ------------------------------------------------------------------------------
    //      Rules
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    public function it_returns_validation_rules_for_a_nested_data_set_with_a_belongs_to_create_data_set(): void
    {
        $data = [
            'title' => 'allowed title',
            'genre' => [
                'name' => 'allowed genre',
            ],
        ];

        $validator = new NestedValidator(Post::class);
        $rules = $validator->validationRules($data);

        $this->assertHasValidationRules($rules, [
            'title'      => ['required', 'string', 'max:50'],
            'body'       => 'string',
            'genre'      => 'array',
            'genre.name' => ['string', 'unique:genres,name'],
        ], true);
    }

    /**
     * @test
     */
    public function it_returns_validation_rules_for_a_nested_data_set_with_a_belongs_to_update_data_set(): void
    {
        $data = [
            'title' => 'allowed title',
            'genre' => [
                'id'   => 13,
                'name' => 'allowed genre',
            ],
        ];

        $validator = new NestedValidator(Post::class);
        $rules = $validator->validationRules($data);

        $this->assertHasValidationRules($rules, [
            'title'      => ['required', 'string', 'max:50'],
            'body'       => 'string',
            'genre'      => 'array',
            'genre.id'   => ['exists:genres,id', 'integer'],
            'genre.name' => ['string', 'unique:genres,name'],
        ], true, true);
    }

    /**
     * Inherent nesting rules for primary keys (key is required, must exist in database)
     * are built up on the basis of data present and what is allowed at a nesting level.
     * This tests whether rules-class set custom rules for the primary key are correctly
     * merged with those rules -- regardless of the format used.
     *
     * @test
     */
    public function it_correctly_merges_custom_model_primary_key_rules_with_inherent_nesting_rules(): void
    {
        Config::set('nestedmodelupdater.relations.' . Post::class . '.tags', [ 'link-only' => true ]);

        $data = [
            'tags' => [
                [
                    'id'   => 13,
                    'name' => 'allowed tag',
                ],
                [
                    'name' => 'new tag!'
                ]
            ]
        ];

        $validator = new NestedValidator(Post::class);
        $rules = $validator->validationRules($data);


        // 'integer' and 'required' should be set and kept inherently by the validator (link-only incrementing key)
        // 'min:2' is a custom rule
        // 'exists:genres,id' is a custom rule that should override the inherently set 'exists:tags,id' rule

        $this->assertHasValidationRules($rules, [
            'tags.0.id' => ['integer', 'required', 'exists:tags,id', 'min:2', 'exists:genres,id'],
            'tags.1.id' => ['integer', 'required', 'exists:tags,id'],
        ], true);
    }

    /**
     * @test
     */
    public function it_returns_correct_validation_rules_for_non_incrementing_nested_relation_model(): void
    {
        $this->createSpecial('special-1');

        $data = [
            'specials' => [
                [
                    'special' => 'special-1',
                    'name'    => 'updated special',
                ],
                [
                    'special' => 'special-2',
                    'name'    => 'updated special',
                ],
            ]
        ];

        $validator = new NestedValidator(Post::class);
        $rules = $validator->validationRules($data);

        // non-incrementing keys are always required, but should only be
        // checked for existance if it can be considered an update
        $this->assertHasValidationRules($rules, [
            'specials.0.special' => ['required', 'exists:specials,special'],
            'specials.1.special' => ['required'],
        ], true);
    }

    /**
     * @test
     */
    public function it_uses_custom_rules_for_a_related_nested_model_if_configured_to(): void
    {
        Config::set('nestedmodelupdater.relations.' . Post::class . '.genre', [
            'rules'        => Genre::class,
            'rules-method' => 'customRulesMethod',
        ]);

        $data = [
            'title' => 'allowed title',
            'genre' => [
                'name' => 'non-custom allowed genre',
            ],
        ];

        $validator = new NestedValidator(Post::class);
        $rules = $validator->validationRules($data);

        $this->assertHasValidationRules($rules, [
            'genre.name' => ['in:custom,rules,work'],
        ], true);
    }

    /**
     * @test
     */
    public function it_uses_model_specific_rules_if_configured_to_unless_nested_relation_rules_overrule_them(): void
    {
        // set up some models to use specific rules classes & methods
        // and set up a single overruling nested relation rules class & method

        Config::set('nestedmodelupdater.validation.model-rules', [
            Post::class => [
                'class' => Post::class,
                'method' => 'customRulesMethod',
            ],
            Tag::class  => Tag::class,
            Genre::class => [
                'class'  => Genre::class,
                'method' => 'notUsedRulesMethod',
            ]
        ]);

        Config::set('nestedmodelupdater.relations.' . Post::class . '.genre', [
            'rules'        => Genre::class,
            'rules-method' => 'customRulesMethod',
        ]);

        $data = [
            'title' => 'allowed title',
            'genre' => [
                'name' => 'non-custom allowed genre',
            ],
            'tags' => [
                [
                    'id'   => 123,
                    'name' => 'some tag',
                ]
            ]
        ];

        $validator = new NestedValidator(Post::class);
        $rules = $validator->validationRules($data);

        $this->assertHasValidationRules($rules, [
            // set for model with array: class & method
            'title'       => 'in:custom,post,rules',
            // set for model with just a class string
            'tags.0.name' => 'in:custom,tag,rules',
            // if genre would not be overridden, it would error on not finding 'notUsedRulesMethod'
            'genre.name'  => 'in:custom,rules,work',
        ], true);
    }


    // ------------------------------------------------------------------------------
    //      Helper methods
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    public function it_returns_create_validation_rules_for_a_model(): void
    {
        $validator = new NestedValidator(Post::class);
        $rules = $validator->getDirectModelValidationRules(false);

        static::assertIsArray($rules);
        static::assertEquals('required|string|max:50', Arr::get($rules, 'title'));
    }

    /**
     * @test
     */
    public function it_returns_update_validation_rules_for_a_model(): void
    {
        $validator = new NestedValidator(Post::class);
        $rules = $validator->getDirectModelValidationRules(false, false);

        static::assertIsArray($rules);
        static::assertEquals('string|max:10', Arr::get($rules, 'title'));
    }

    /**
     * @test
     */
    public function it_returns_empty_validation_rules_if_rules_model_not_found(): void
    {
        $validator = new NestedValidator(Special::class);
        $rules = $validator->getDirectModelValidationRules();

        static::assertIsArray($rules);
        static::assertCount(0, $rules);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_attempting_to_retrieve_nonexistent_rules_if_configured_to(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessageMatches('#not bound#i');

        Config::set('nestedmodelupdater.validation.allow-missing-rules', false);

        $validator = new NestedValidator(Special::class);
        $validator->getDirectModelValidationRules();
    }


    // ------------------------------------------------------------------------------
    //      Exceptions
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    public function it_throws_an_exception_if_a_rules_class_for_a_model_does_not_have_the_rules_method(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessageMatches('#no method \'rules\'#i');

        // set a 'rules' class that does not have rules()
        Config::set('nestedmodelupdater.relations.' . Post::class . '.genre', [ 'rules' => Post::class ]);

        $data = [
            'title' => 'allowed title',
            'genre' => [
                'name' => 'genre'
            ],
        ];

        $validator = new NestedValidator(Post::class);
        $validator->validate($data);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_a_rules_class_method_does_not_return_an_array(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessageMatches('#array#i');

        Config::set('nestedmodelupdater.relations.' . Post::class . '.genre', [
            'rules'        => Genre::class,
            'rules-method' => 'brokenCustomRulesMethod',
        ]);

        $data = [
            'title' => 'allowed title',
            'genre' => [
                'name' => 'genre'
            ],
        ];

        $validator = new NestedValidator(Post::class);
        $validator->validate($data);
    }
}
