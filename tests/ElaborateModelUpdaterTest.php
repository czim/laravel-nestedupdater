<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection ReturnTypeCanBeDeclaredInspection */
/** @noinspection AccessModifierPresentedInspection */

namespace Czim\NestedModelUpdater\Test;

use Czim\NestedModelUpdater\ModelUpdater;
use Czim\NestedModelUpdater\Test\Helpers\Models\Author;
use Czim\NestedModelUpdater\Test\Helpers\Models\Comment;
use Czim\NestedModelUpdater\Test\Helpers\Models\Genre;
use Czim\NestedModelUpdater\Test\Helpers\Models\Post;
use Czim\NestedModelUpdater\Test\Helpers\Models\Special;

class ElaborateModelUpdaterTest extends TestCase
{

    /**
     * @test
     */
    function it_creates_and_updates_a_nested_hasmany_relation()
    {
        $post    = $this->createPost();
        $comment = $this->createComment($post);

        $data = [
            'comments' => [
                [
                    'id'    => $comment->id,
                    'title' => 'updated title',
                ],
                [
                    'title' => 'new title',
                    'body'  => 'new body',
                ]
            ]
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->assertDatabaseHas('comments', [
            'id'    => $comment->id,
            'title' => 'updated title',
        ]);

        $this->assertDatabaseHas('comments', [
            'title' => 'new title',
            'body'  => 'new body',
        ]);
    }

    /**
     * @test
     */
    function it_creates_and_updates_a_nested_hasone_relation()
    {
        $post = $this->createPost();

        $data = [
            'comment_has_one' => [
                'title' => 'created title',
                'body'  => 'created body',
            ],
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->assertDatabaseHas('comments', [
            'title' => 'created title',
            'body'  => 'created body',
        ]);

        $comment = $post->commentHasOne()->first();
        static::assertInstanceOf(Comment::class, $comment);


        // and update it

        $data = [
            'comment_has_one' => [
                'id'    => $comment->id,
                'title' => 'updated title',
            ],
        ];

        $updater->update($data, $post);

        $this->assertDatabaseHas('comments', [
            'id'    => $comment->id,
            'title' => 'updated title',
            'body'  => 'created body',
        ]);
    }

    /**
     * @test
     */
    function it_creates_and_updates_a_nested_belongstomany_relation()
    {
        $post   = $this->createPost();
        $author = $this->createAuthor();

        $data = [
            'posts' => [
                [
                    'id'     => $post->id,
                    'title'  => 'updated title',
                    'body'   => 'updated body',
                ],
                [
                    'title'  => 'very new title',
                    'body'   => 'very new body',
                ]
            ]
        ];

        $updater = new ModelUpdater(Author::class);
        $updater->update($data, $author);

        $this->assertDatabaseHas('posts', [
            'id'    => $post->id,
            'title' => 'updated title',
            'body'  => 'updated body',
        ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'very new title',
            'body'  => 'very new body',
        ]);

        $this->assertDatabaseHas('author_post', [
            'post_id'   => $post->id,
            'author_id' => $author->id,
        ]);
    }

    /**
     * @test
     */
    function it_creates_and_updates_a_nested_morphmany_relation()
    {
        $post = $this->createPost();
        $tag  = $this->createTag();

        $data = [
            'tags' => [
                [
                    'id'   => $tag->id,
                    'name' => 'updated tag',
                ],
                [
                    'name' => 'new tag',
                ]
            ]
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->assertDatabaseHas('tags', [
            'id'            => $tag->id,
            'taggable_id'   => $post->id,
            'taggable_type' => get_class($post),
            'name'          => 'updated tag',
        ]);

        $this->assertDatabaseHas('tags', [
            'taggable_id'   => $post->id,
            'taggable_type' => get_class($post),
            'name'          => 'new tag',
        ]);
    }

    /**
     * @test
     */
    function it_updates_a_nested_related_record_with_nonstandard_primary_key()
    {
        $post    = $this->createPost();
        $special = $this->createSpecial('special-1');

        $data = [
            'specials' => [
                [
                    'special' => $special->getKey(),
                    'name'    => 'updated special',
                ],
            ]
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->assertDatabaseHas('specials', [
            'special' => $special->getKey(),
            'name'    => 'updated special',
        ]);
    }

    /**
     * @test
     */
    function it_creates_a_nested_related_record_with_nonincrementing_primary_key_if_the_key_does_not_exist()
    {
        $post = $this->createPost();

        $data = [
            'specials' => [
                [
                    'special' => 'special-new',
                    'name'    => 'new special',
                ],
            ]
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->assertDatabaseHas('specials', [
            'special' => 'special-new',
            'name'    => 'new special',
        ]);
    }

    /**
     * @test
     */
    function it_links_a_nested_related_record_with_nonincrementing_primary_key_if_it_exists()
    {
        $post    = $this->createPost();
        $special = $this->createSpecial('special-exists', 'original name');
        $special->post()->associate($post);
        $special->save();

        $data = [
            'specials' => [
                [
                    'special' => 'special-exists',
                    'name'    => 'updated name',
                ],
            ]
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->assertEquals(1, Special::count(), 'There should be only 1 Special record');

        $this->assertDatabaseHas('specials', [
            'special' => 'special-exists',
            'name'    => 'updated name',
        ]);
    }

    // ------------------------------------------------------------------------------
    //      Detaching and Deleting Omitted
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_deletes_omitted_nested_belongsto_relations_on_replacement_and_dissociation_if_configured_to()
    {
        $this->app['config']->set('nestedmodelupdater.relations.' . Post::class . '.genre.delete-detached', true);

        $post  = $this->createPost();
        $genre = $this->createGenre();
        $post->genre()->associate($genre);
        $post->save();

        $oldGenreId = $genre->id;

        $this->assertDatabaseHas('posts', [ 'id' => $post->id, 'genre_id' => $oldGenreId ]);

        // check if it is deleted after dissociation

        $data = [
            'genre' => null,
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->assertDatabaseHas('posts', [ 'id' => $post->id, 'genre_id' => null ]);
        $this->assertDatabaseMissing('genres', [ 'id' => $oldGenreId ]);

        // reset

        $post->delete();
        $post  = $this->createPost();
        $genre = $this->createGenre();
        $post->genre()->associate($genre);
        $post->save();

        $oldGenreId = $genre->id;

        $this->assertDatabaseHas('posts', [ 'id' => $post->id, 'genre_id' => $oldGenreId ]);

        // check if it is deleted after replacement

        $data = [
            'genre' => [
                'name' => 'replacement genre'
            ],
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $genre = Genre::where('name', 'replacement genre')->first();
        static::assertInstanceOf(Genre::class, $genre);

        $this->assertDatabaseHas('posts', [ 'id' => $post->id, 'genre_id' => $genre->id ]);
        $this->assertDatabaseMissing('genres', [ 'id' => $oldGenreId ]);
    }

    /**
     * @test
     */
    function it_detaches_omitted_nested_belongstomany_relations()
    {
        $this->app['config']->set('nestedmodelupdater.relations.' . Post::class . '.authors.link-only', false);

        // setup

        $post    = $this->createPost();
        $authorA = $this->createAuthor();
        $authorB = $this->createAuthor();
        $post->authors()->sync([ $authorA->id, $authorB->id ]);

        $this->assertDatabaseHas('author_post', [ 'post_id' => $post->id, 'author_id' => $authorA->id ]);
        $this->assertDatabaseHas('author_post', [ 'post_id' => $post->id, 'author_id' => $authorB->id ]);

        $data = [
            'authors' => [
                $authorA->id,
                [
                    'name'   => 'New Author',
                    'gender' => 'f',
                ]
            ],
        ];

        // Test

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $authorC = Author::latest()->first();
        static::assertInstanceOf(Author::class, $authorC);

        $this->assertDatabaseHas('authors', [ 'id' => $authorB->id ]);
        $this->assertDatabaseHas('author_post',     [ 'post_id' => $post->id, 'author_id' => $authorA->id ]);
        $this->assertDatabaseHas('author_post',     [ 'post_id' => $post->id, 'author_id' => $authorC->id ]);
        $this->assertDatabaseMissing('author_post', [ 'post_id' => $post->id, 'author_id' => $authorB->id ]);
    }

    /**
     * @test
     */
    function it_does_not_detach_omitted_nested_belongstomany_relations_if_configured_not_to()
    {
        $this->app['config']->set('nestedmodelupdater.relations.' . Post::class . '.authors.detach', false);

        // setup

        $post    = $this->createPost();
        $authorA = $this->createAuthor();
        $authorB = $this->createAuthor();
        $post->authors()->sync([ $authorA->id, $authorB->id ]);

        $this->assertDatabaseHas('author_post', [ 'post_id' => $post->id, 'author_id' => $authorA->id ]);
        $this->assertDatabaseHas('author_post', [ 'post_id' => $post->id, 'author_id' => $authorB->id ]);

        $data = [
            'authors' => [
                $authorA->id,
            ],
        ];

        // test

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->assertDatabaseHas('authors', [ 'id' => $authorB->id ]);
        $this->assertDatabaseHas('author_post', [ 'post_id' => $post->id, 'author_id' => $authorA->id ]);
        $this->assertDatabaseHas('author_post', [ 'post_id' => $post->id, 'author_id' => $authorB->id ]);
    }


    /**
     * @test
     */
    function it_deletes_detached_belongstomany_related_records_if_configured_to()
    {
        $this->app['config']->set('nestedmodelupdater.relations.' . Post::class . '.authors.delete-detached', true);
        $this->app['config']->set('nestedmodelupdater.relations.' . Post::class . '.authors.link-only', false);

        // setup

        $post    = $this->createPost();
        $authorA = $this->createAuthor();
        $authorB = $this->createAuthor();
        $post->authors()->sync([ $authorA->id, $authorB->id ]);

        $this->assertDatabaseHas('author_post', [ 'post_id' => $post->id, 'author_id' => $authorA->id ]);
        $this->assertDatabaseHas('author_post', [ 'post_id' => $post->id, 'author_id' => $authorB->id ]);

        $data = [
            'authors' => [
                $authorA->id,
            ],
        ];

        // test

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->assertDatabaseHas('author_post',     [ 'post_id' => $post->id, 'author_id' => $authorA->id ]);
        $this->assertDatabaseMissing('author_post', [ 'post_id' => $post->id, 'author_id' => $authorB->id ]);
        $this->assertDatabaseMissing('authors', [ 'id' => $authorB->id ]);
    }

    /**
     * @test
     */
    function it_does_not_delete_detached_belongstomany_related_records_if_they_are_related_to_other_models()
    {
        $this->app['config']->set('nestedmodelupdater.relations.' . Post::class . '.authors.delete-detached', true);
        $this->app['config']->set('nestedmodelupdater.relations.' . Post::class . '.authors.link-only', false);

        // setup

        $post    = $this->createPost();
        $authorA = $this->createAuthor();
        $authorB = $this->createAuthor();
        $post->authors()->sync([ $authorA->id, $authorB->id ]);

        $otherPost = $this->createPost();
        $otherPost->authors()->sync([ $authorB->id ]);

        $data = [
            'authors' => [
                $authorA->id,
            ],
        ];

        // test

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->assertDatabaseHas('author_post',     [ 'post_id' => $post->id, 'author_id' => $authorA->id ]);
        $this->assertDatabaseMissing('author_post', [ 'post_id' => $post->id, 'author_id' => $authorB->id ]);
        $this->assertDatabaseHas('authors', [ 'id' => $authorB->id ]);
    }

    /**
     * @test
     */
    function it_detaches_omitted_nested_hasmany_and_hasone_relations()
    {
        $this->app['config']->set('nestedmodelupdater.relations.' . Author::class . '.comments.detach', true);

        // setup

        $author   = $this->createAuthor();
        $post     = $this->createPost();
        $commentA = $this->createComment($post);
        $commentB = $this->createComment($post);

        $author->comments()->save($commentA);
        $author->comments()->save($commentB);

        $this->assertDatabaseHas('comments', [ 'id' => $commentA->id, 'author_id' => $author->id ]);

        $data = [
            'comments' => [
                $commentB->id,
            ],
        ];


        // test

        $updater = new ModelUpdater(Author::class);
        $updater->update($data, $author);


        $this->assertDatabaseHas('comments', [ 'id' => $commentA->id, 'author_id' => null ]);
        $this->assertDatabaseHas('comments', [ 'id' => $commentB->id, 'author_id' => $author->id ]);
    }

    /**
     * @test
     */
    function it_deletes_detached_hasmany_and_hasone_related_records_if_configured_to()
    {
        $this->app['config']->set('nestedmodelupdater.relations.' . Post::class . '.comments.detach', true);
        $this->app['config']->set('nestedmodelupdater.relations.' . Post::class . '.comments.delete-detached', true);

        // setup

        $post      = $this->createPost();
        $otherPost = $this->createPost();
        $commentA  = $this->createComment($post);
        $commentB  = $this->createComment($post);
        $commentC  = $this->createComment($otherPost);

        $data = [
            'comments' => [
                $commentB->id,
                $commentC->id,
            ],
        ];

        // test

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->assertDatabaseMissing('comments', [ 'id' => $commentA->id ]);
        $this->assertDatabaseHas('comments',     [ 'id' => $commentB->id, 'post_id' => $post->id ]);
        $this->assertDatabaseHas('comments',     [ 'id' => $commentC->id, 'post_id' => $post->id ]);
    }

    // ------------------------------------------------------------------------------
    //      Full Stack
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_creates_deeply_nested_structure_of_all_new_models()
    {
        // allow creating authors through posts
        $this->app['config']->set('nestedmodelupdater.relations.' . Post::class . '.authors', [
            'link-only' => false,
        ]);

        $data = [
            'title' => 'new title',
            'body' => 'new body',
            'genre' => [
                'name' => 'new genre',
            ],
            'comments' => [
                [
                    'title'  => 'title 1',
                    'body'   => 'body 1',
                    'author' => [
                        'name' => 'Author B',
                    ],
                ],
                [
                    'title'  => 'title 2',
                    'body'   => 'body 2',
                    'author' => [
                        'name' => 'Author C',
                    ],
                ]
            ],
            'authors' => [
                [
                    'name'   => 'Author A',
                    'gender' => 'f',
                ],
            ],
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->create($data);

        // check the whole structure

        $post = Post::latest()->first();
        static::assertInstanceOf(Post::class, $post);

        $genre = Genre::latest()->first();
        static::assertInstanceOf(Genre::class, $genre);

        $commentAuthorB = Author::where('name', 'Author B')->first();
        static::assertInstanceOf(Author::class, $commentAuthorB);
        $commentAuthorC = Author::where('name', 'Author C')->first();
        static::assertInstanceOf(Author::class, $commentAuthorC);

        $this->assertDatabaseHas('posts', [
            'id'       => $post->id,
            'title'    => 'new title',
            'body'     => 'new body',
            'genre_id' => $genre->id,
        ]);

        $this->assertDatabaseHas('genres', [
            'id'   => $genre->id,
            'name' => 'new genre',
        ]);

        $this->assertDatabaseHas('comments', [
            'title'     => 'title 1',
            'body'      => 'body 1',
            'author_id' => $commentAuthorB->id,
        ]);

        $this->assertDatabaseHas('comments', [
            'title'     => 'title 2',
            'body'      => 'body 2',
            'author_id' => $commentAuthorC->id,
        ]);

        $this->assertDatabaseHas('authors', [
            'name' => 'Author A',
        ]);
    }

    /**
     * @test
     */
    function it_creates_a_deeply_nested_structure_with_linked_models()
    {
        $genre   = $this->createGenre();
        $authorA = $this->createAuthor();
        $authorB = $this->createAuthor();

        $data = [
            'title' => 'new title',
            'body' => 'new body',
            'genre' => $genre->id,
            'comments' => [
                [
                    'title'  => 'title 1',
                    'body'   => 'body 1',
                    'author' => [
                        'id' => $authorA->id,
                    ],
                ],
                [
                    'title'  => 'title 2',
                    'body'   => 'body 2',
                    'author' => $authorB->id,
                ]
            ],
            'authors' => [
                $authorA->id,
                [
                    'id' => $authorB->id,
                ],
            ],
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->create($data);

        // check the whole structure

        $post = Post::latest()->first();
        static::assertInstanceOf(Post::class, $post);

        $this->assertDatabaseHas('posts', [
            'id'       => $post->id,
            'title'    => 'new title',
            'body'     => 'new body',
            'genre_id' => $genre->id,
        ]);

        $this->assertDatabaseHas('comments', [
            'title'     => 'title 1',
            'body'      => 'body 1',
            'author_id' => $authorA->id,
        ]);

        $this->assertDatabaseHas('comments', [
            'title'     => 'title 2',
            'body'      => 'body 2',
            'author_id' => $authorB->id,
        ]);

        $this->assertDatabaseHas('author_post', [
            'post_id'   => $post->id,
            'author_id' => $authorA->id,
        ]);

        $this->assertDatabaseHas('author_post', [
            'post_id'   => $post->id,
            'author_id' => $authorB->id,
        ]);
    }


    // ------------------------------------------------------------------------------
    //      Unguarded Attributes
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_sets_and_clears_unguarded_attributes()
    {
        $updater = new ModelUpdater(Post::class);

        $updater->setUnguardedAttribute('unfillable', 'testing');
        static::assertEquals([ 'unfillable' => 'testing' ], $updater->getUnguardedAttributes());

        $updater->setUnguardedAttributes([ 'test' => 'a', 'test_b' => '2' ]);
        static::assertEquals([ 'test' => 'a', 'test_b' => '2' ], $updater->getUnguardedAttributes());

        $updater->clearUnguardedAttributes();
        static::assertEmpty($updater->getUnguardedAttributes());
    }

    /**
     * @test
     */
    function it_updates_an_unguarded_unfillable_attribute()
    {
        $post = $this->createPost();

        $data = [
            'comments' => [
                [
                    'title' => 'new title',
                    'body'  => 'new body',
                ]
            ]
        ];

        $updater = new ModelUpdater(Post::class);

        $updater->setUnguardedAttribute('unfillable', 'testing');
        $updater->update($data, $post);

        $this->assertDatabaseHas('posts', [
            'id'         => $post->id,
            'unfillable' => 'testing',
        ]);

        $this->assertDatabaseHas('comments', [
            'title' => 'new title',
            'body'  => 'new body',
        ]);
    }

    /**
     * @test
     */
    function it_clears_unguarded_attributes_after_update()
    {
        $post = $this->createPost();

        $data = [];

        $updater = new ModelUpdater(Post::class);

        $updater->setUnguardedAttribute('unfillable', 'testing');

        static::assertNotEmpty($updater->getUnguardedAttributes());

        $updater->update($data, $post);

        static::assertEmpty($updater->getUnguardedAttributes());
    }

}
