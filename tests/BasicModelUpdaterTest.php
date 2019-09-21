<?php
/** @noinspection ReturnTypeCanBeDeclaredInspection */
/** @noinspection AccessModifierPresentedInspection */

namespace Czim\NestedModelUpdater\Test;

use Czim\NestedModelUpdater\Data\UpdateResult;
use Czim\NestedModelUpdater\Exceptions\DisallowedNestedActionException;
use Czim\NestedModelUpdater\Exceptions\NestedModelNotFoundException;
use Czim\NestedModelUpdater\ModelUpdater;
use Czim\NestedModelUpdater\Test\Helpers\ArrayableData;
use Czim\NestedModelUpdater\Test\Helpers\Models\Post;

class BasicModelUpdaterTest extends TestCase
{

    // ------------------------------------------------------------------------------
    //      Basics
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_creates_a_model_without_any_nested_relations()
    {
        $data = [
            'title' => 'created',
            'body'  => 'fresh',
        ];

        $updater = new ModelUpdater(Post::class);
        $result = $updater->create($data);

        $this->assertInstanceOf(UpdateResult::class, $result);
        $this->assertTrue($result->model()->exists, 'Created model should exist');

        $this->assertDatabaseHas('posts', [
            'id'    => $result->model()->id,
            'title' => 'created',
            'body'  => 'fresh',
        ]);
    }

    /**
     * @test
     */
    function it_updates_a_model_without_any_nested_relations()
    {
        $post = $this->createPost();

        $data = [
            'title' => 'updated',
            'body'  => 'fresh',
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->assertDatabaseHas('posts', [
            'id'    => $post->id,
            'title' => 'updated',
            'body'  => 'fresh',
        ]);
    }

    /**
     * @test
     */
    function it_creates_a_new_nested_model_related_as_belongs_to()
    {
        $post = $this->createPost();

        $data = [
            'title' => 'updated aswell',
            'genre' => [
                'name' => 'New Genre',
            ],
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->assertDatabaseHas('posts', [
            'id'       => $post->id,
            'title'    => 'updated aswell',
        ]);

        $post = Post::find($post->id);

        $this->assertEquals(1, $post->genre_id, "New Genre should be associated with Post");

        $this->assertDatabaseHas('genres', [
            'name' => 'New Genre',
        ]);
    }

    /**
     * @test
     */
    function it_updates_a_new_nested_model_related_as_belongs_to_without_updating_parent()
    {
        $post  = $this->createPost();
        $genre = $this->createGenre('original name');
        $post->genre()->associate($genre);
        $post->save();

        // disallow full updates
        $this->app['config']->set('nestedmodelupdater.relations.' . Post::class . '.genre', [
            'link-only' => true,
        ]);

        $originalPostData = [
            'id'    => $post->id,
            'title' => $post->title,
            'body'  => $post->body,
        ];

        $data = [
            'genre' => [
                'id'   => $genre->id,
                'name' => 'updated name',
            ],
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->assertDatabaseHas('posts', $originalPostData);

        $this->assertDatabaseHas('genres', [
            'id'   => $genre->id,
            'name' => 'original name',
        ]);
    }

    /**
     * @test
     */
    function it_only_links_a_related_model_if_no_update_is_allowed()
    {
        $post  = $this->createPost();
        $genre = $this->createGenre();
        $post->genre()->associate($genre);
        $post->save();

        $originalPostData = [
            'id'    => $post->id,
            'title' => $post->title,
            'body'  => $post->body,
        ];

        $data = [
            'genre' => [
                'id'   => $genre->id,
                'name' => 'updated',
            ],
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->assertDatabaseHas('posts', $originalPostData);

        $this->assertDatabaseHas('genres', [
            'id'   => $genre->id,
            'name' => 'updated',
        ]);
    }

    /**
     * @test
     */
    function it_dissociates_a_belongs_to_relation_if_empty_data_is_passed_in()
    {
        $post  = $this->createPost();
        $genre = $this->createGenre();
        $post->genre()->associate($genre);
        $post->save();

        $data = [
            'genre' => [],
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->assertDatabaseHas('posts', [
            'id'       => $post->id,
            'genre_id' => null,
        ]);
    }

    /**
     * @test
     */
    function it_attaches_has_many_related_models_that_were_related_to_a_different_model()
    {
        $post      = $this->createPost();
        $otherPost = $this->createPost();
        $commentA  = $this->createComment($otherPost);
        $commentB  = $this->createComment($otherPost);

        $this->assertDatabaseHas('comments', [ 'id' => $commentA->id, 'post_id' => $otherPost->id ]);
        $this->assertDatabaseHas('comments', [ 'id' => $commentB->id, 'post_id' => $otherPost->id ]);

        $data = [
            'comments' => [
                $commentA->id,
                [ 'id' => $commentB->id ],
            ],
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->assertDatabaseHas('comments', [ 'id' => $commentA->id, 'post_id' => $post->id ]);
        $this->assertDatabaseHas('comments', [ 'id' => $commentB->id, 'post_id' => $post->id ]);
    }

    // ------------------------------------------------------------------------------
    //      Normalization
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_normalizes_nested_data_for_null_value()
    {
        $post  = $this->createPost();
        $post->genre()->associate($post);
        $post->save();

        $data = [
            'genre' => null,
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->assertDatabaseHas('posts', [
            'id'       => $post->id,
            'genre_id' => null,
        ]);
    }

    /**
     * @test
     */
    function it_normalizes_nested_data_for_scalar_link_value()
    {
        $post  = $this->createPost();
        $genre = $this->createGenre('original name');

        $data = [
            'genre' => $genre->id,
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->assertDatabaseHas('posts', [
            'id'       => $post->id,
            'genre_id' => $genre->id,
        ]);
    }

    /**
     * @test
     */
    function it_normalizes_nested_data_for_arrayable_content()
    {
        $post  = $this->createPost();
        $genre = $this->createGenre('original name');

        $data = [
            'genre' => new ArrayableData([
                'id'   => $genre->id,
                'name' => 'updated',
            ])
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->assertDatabaseHas('posts', [
            'id'       => $post->id,
            'genre_id' => $genre->id,
        ]);

        $this->assertDatabaseHas('genres', [
            'id'   => $genre->id,
            'name' => 'updated',
        ]);
    }


    // ------------------------------------------------------------------------------
    //      Problems and exceptions
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_throws_an_exception_if_nested_relation_data_is_of_incorrect_type()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessageRegExp('#genre\)#i');

        $post  = $this->createPost();

        $data = [
            'genre' => (object) [ 'incorrect' => 'data' ],
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);
    }

    /**
     * @test
     */
    function it_throws_an_exception_if_it_cannot_find_the_top_level_model_by_id()
    {
        $this->expectException(NestedModelNotFoundException::class);
        $this->expectExceptionMessageRegExp('#Czim\\\\NestedModelUpdater\\\\Test\\\\Helpers\\\\Models\\\\Post#');

        $data = [
            'genre' => [
                'name' => 'updated',
            ],
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, 999);
    }

    /**
     * @test
     */
    function it_throws_an_exception_with_nested_key_if_it_cannot_find_a_nested_model_by_id()
    {
        $this->expectException(NestedModelNotFoundException::class);
        $this->expectExceptionMessageRegExp('#Czim\\\\NestedModelUpdater\\\\Test\\\\Helpers\\\\Models\\\\Genre.*\(nesting: genre\)#i');

        $post = $this->createPost();

        $data = [
            'genre' => [
                'id'   => 999,  // does not exist
                'name' => 'updated',
            ],
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);
    }

    /**
     * @test
     */
    function it_throws_an_exception_if_not_allowed_to_create_a_nested_model_record_that_has_no_id()
    {
        $this->expectException(DisallowedNestedActionException::class);
        $this->expectExceptionMessageRegExp('#authors\.0#i');

        $data = [
            'title' => 'Problem Post',
            'body'  => 'Body',
            'authors' => [
                [ 'name' => 'New Name' ]
            ]
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->create($data);
    }

    /**
     * @test
     */
    function it_throws_an_exception_if_not_allowed_to_create_an_update_only_nested_model_record()
    {
        $this->expectException(DisallowedNestedActionException::class);
        $this->expectExceptionMessageRegExp('#authors\.0#i');

        $this->app['config']->set('nestedmodelupdater.relations.' . Post::class . '.authors', [
            'link-only'   => false,
            'update-only' => true,
        ]);

        $data = [
            'title' => 'Problem Post',
            'body'  => 'Body',
            'authors' => [
                [ 'name' => 'New Name' ]
            ]
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->create($data);
    }

    /**
     * @test
     */
    function it_rolls_back_changes_if_exception_is_thrown()
    {
        $post = $this->createPost();

        $data = [
            'title' => 'this should be',
            'body'  => 'rolled back',
            // comments is a HasMany relation, so the model is
            // updated and persisted before this is parsed
            'comments' => [
                [
                    'id' => 999,  // does not exist
                ]
            ],
        ];

        $updater = new ModelUpdater(Post::class);

        try {
            $updater->update($data, $post);

            // should never get here
            $this->fail('Exception should have been thrown while attempting update');

        } catch (NestedModelNotFoundException $e) {
            // expected
        }

        // unchanged data
        $this->assertDatabaseMissing('posts', [
            'title' => 'this should be',
            'body'  => 'rolled back',
        ]);
    }

    /**
     * @test
     */
    function it_can_be_configured_not_to_use_database_transactions()
    {
        $post = $this->createPost();

        $data = [
            'title' => 'this should be',
            'body'  => 'rolled back',
            // comments is a HasMany relation, so the model is
            // updated and persisted before this is parsed
            'comments' => [
                [
                    'id' => 999,  // does not exist
                ]
            ],
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->disableDatabaseTransaction();

        try {
            $updater->update($data, $post);

            // should never get here
            $this->fail('Exception should have been thrown while attempting update');

        } catch (NestedModelNotFoundException $e) {
            // expected
        }

        // unchanged data
        $this->assertDatabaseHas('posts', [
            'title' => 'this should be',
            'body'  => 'rolled back',
        ]);
    }

}
