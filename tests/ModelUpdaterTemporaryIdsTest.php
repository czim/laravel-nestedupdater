<?php
/** @noinspection ReturnTypeCanBeDeclaredInspection */
/** @noinspection AccessModifierPresentedInspection */

namespace Czim\NestedModelUpdater\Test;

use Czim\NestedModelUpdater\Exceptions\InvalidNestedDataException;
use Illuminate\Support\Facades\Config;
use Czim\NestedModelUpdater\ModelUpdater;
use Czim\NestedModelUpdater\Test\Helpers\Models\Author;
use Czim\NestedModelUpdater\Test\Helpers\Models\Comment;
use Czim\NestedModelUpdater\Test\Helpers\Models\Genre;
use Czim\NestedModelUpdater\Test\Helpers\Models\Post;

class ModelUpdaterTemporaryIdsTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();

        Config::set('nestedmodelupdater.allow-temporary-ids', true);
    }

    /**
     * @test
     */
    function it_creates_and_updates_a_nested_relation_using_temporary_ids()
    {
        $post    = $this->createPost();
        $comment = $this->createComment($post);

        $data = [
            'comments' => [
                [
                    'id'     => $comment->id,
                    'title'  => 'updated title',
                    'author' => [
                        '_tmp_id' => 'auth_1',
                        'name'    => 'new shared author',
                    ],
                ],
                [
                    'title'  => 'new title',
                    'body'   => 'for new comment',
                    'author' => [
                        '_tmp_id' => 'auth_1',
                    ]
                ]
            ]
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->assertEquals(1, Author::count(), 'Exactly one author should have been created');

        /** @var Author $author */
        $author = Author::first();

        $this->assertDatabaseHas('comments', [
            'id'    => $comment->id,
            'title' => 'updated title',
        ]);

        $this->assertDatabaseHas('comments', [
            'title' => 'new title',
            'body'  => 'for new comment',
        ]);

        $this->assertDatabaseHas('authors', [
            'id'   => $author->id,
            'name' => 'new shared author',
        ]);
    }

    /**
     * @test
     */
    function it_creates_and_updates_a_nested_relation_using_multiple_distinct_temporary_ids()
    {
        $post    = $this->createPost();
        $comment = $this->createComment($post);

        $data = [
            'comments' => [
                [
                    'id'     => $comment->id,
                    'title'  => 'updated title',
                    'author' => [
                        '_tmp_id' => 'auth_1',
                        'name'    => 'new shared author',
                        'posts'   => [
                            [
                                'title' => 'new nested title',
                                'body'  => 'new nested body',
                                'genre' => [
                                    '_tmp_id' => 'genre_2'
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'title'  => 'new title',
                    'body'   => 'for new comment',
                    'author' => [
                        '_tmp_id' => 'auth_1',
                    ]
                ]
            ],
            'genre' => [
                '_tmp_id' => 'genre_2',
                'name'    => 'new shared genre',
            ]
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->assertEquals(1, Author::count(), 'Exactly one author should have been created');
        /** @var Author $author */
        $author = Author::first();

        $this->assertEquals(1, Genre::count(), 'Exactly one tag should have been created');
        /** @var Genre $genre */
        $genre = Genre::first();

        $this->assertEquals(2, Post::count(), 'Exactly two posts should exist (1 created by nesting)');
        /** @var Post $newPost */
        $newPost = Post::orderBy('id', 'desc')->first();

        $this->assertDatabaseHas('comments', [
            'id'    => $comment->id,
            'title' => 'updated title',
        ]);

        $this->assertDatabaseHas('comments', [
            'title' => 'new title',
            'body'  => 'for new comment',
        ]);

        $this->assertDatabaseHas('authors', [
            'id'   => $author->id,
            'name' => 'new shared author',
        ]);

        $this->assertDatabaseHas('posts', [
            'id'       => $post->id,
            'genre_id' => $genre->id,
        ]);

        $this->assertDatabaseHas('posts', [
            'id'       => $newPost->id,
            'title'    => 'new nested title',
            'genre_id' => $genre->id,
        ]);

        $this->assertDatabaseHas('genres', [
            'id'   => $genre->id,
            'name' => 'new shared genre',
        ]);
    }


    // ------------------------------------------------------------------------------
    //      Exceptions
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_throws_an_exception_if_a_temporary_id_is_used_for_different_models()
    {
        $this->expectException(InvalidNestedDataException::class);
        $this->expectExceptionMessageRegExp('#[\'"]auth_1[\'"]#');

        $post    = $this->createPost();
        $comment = $this->createComment($post);

        $data = [
            'comments' => [
                [
                    'id'     => $comment->id,
                    'title'  => 'updated title',
                    'author' => [
                        '_tmp_id' => 'auth_1',
                        'name'    => 'new author',
                    ]
                ],
                [
                    '_tmp_id' => 'auth_1',
                    'title'   => 'new title',
                    'body'    => 'for new comment',
                ]
            ]
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);
    }

    /**
     * @test
     */
    function it_throws_an_exception_if_a_no_data_is_defined_for_a_temporary_id()
    {
        $this->expectException(InvalidNestedDataException::class);
        $this->expectExceptionMessageRegExp('#data defined.*[\'"]auth_1[\'"]#');

        $post    = $this->createPost();
        $comment = $this->createComment($post);

        $data = [
            'comments' => [
                [
                    'id'     => $comment->id,
                    'title'  => 'updated title',
                    'author' => [
                        '_tmp_id' => 'auth_1',
                    ]
                ],
                [
                    'title'  => 'new title',
                    'body'   => 'for new comment',
                    'author' => [
                        '_tmp_id' => 'auth_1',
                    ]
                ]
            ]
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);
    }

    /**
     * @test
     */
    function it_throws_an_exception_if_a_create_data_for_a_temporary_id_contains_a_primary_key_value()
    {
        $this->expectException(InvalidNestedDataException::class);
        $this->expectExceptionMessageRegExp('#[\'"]auth_1[\'"].*primary key#');

        $post    = $this->createPost();
        $comment = $this->createComment($post);

        $data = [
            'comments' => [
                [
                    'id'     => $comment->id,
                    'title'  => 'updated title',
                    'author' => [
                        '_tmp_id' => 'auth_1',
                    ]
                ],
                [
                    'title'  => 'new title',
                    'body'   => 'for new comment',
                    'author' => [
                        '_tmp_id' => 'auth_1',
                        'id'      => 123,
                    ]
                ]
            ]
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);
    }

    /**
     * @test
     */
    function it_throws_an_exception_if_multiple_inconsistent_sets_of_create_data_for_a_temporary_id_are_defined()
    {
        $this->expectException(InvalidNestedDataException::class);
        $this->expectExceptionMessageRegExp('#inconsistent.*[\'"]auth_1[\'"]#');

        $post    = $this->createPost();
        $comment = $this->createComment($post);

        $data = [
            'comments' => [
                [
                    'id'     => $comment->id,
                    'title'  => 'updated title',
                    'author' => [
                        '_tmp_id' => 'auth_1',
                        'name'    => 'Some Author Name',
                    ]
                ],
                [
                    'title'  => 'new title',
                    'body'   => 'for new comment',
                    'author' => [
                        '_tmp_id' => 'auth_1',
                        'name'    => 'Not The Same Author Name',
                    ]
                ]
            ]
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);
    }

    /**
     * @test
     */
    function it_throws_an_exception_if_not_allowed_to_create_for_any_nested_use_of_a_temporary_id()
    {
        $this->expectException(InvalidNestedDataException::class);
        $this->expectExceptionMessageRegExp('#allowed.*[\'"]auth_1[\'"]#');

        Config::set('nestedmodelupdater.relations.'  . Comment::class . '.author', [ 'update-only' => true ]);

        $post    = $this->createPost();
        $comment = $this->createComment($post);

        $data = [
            'comments' => [
                [
                    'id'     => $comment->id,
                    'title'  => 'updated title',
                    'author' => [
                        '_tmp_id' => 'auth_1',
                        'name'    => 'Some Author Name',
                    ]
                ],
                [
                    'title'  => 'new title',
                    'body'   => 'for new comment',
                    'author' => [
                        '_tmp_id' => 'auth_1',
                    ]
                ]
            ]
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);
    }

}
