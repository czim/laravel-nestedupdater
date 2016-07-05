<?php
namespace Czim\NestedModelUpdater\Test;

use Config;
use Czim\NestedModelUpdater\ModelUpdater;
use Czim\NestedModelUpdater\Test\Helpers\Models\Author;
use Czim\NestedModelUpdater\Test\Helpers\Models\Post;

class ModelUpdaterTemporaryIdsTest extends TestCase
{

    public function setUp()
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

        $this->assertEquals(1, Author::count(), "Exactly one author should have been created");

        /** @var Author $author */
        $author = Author::first();

        $this->seeInDatabase('comments', [
            'id'    => $comment->id,
            'title' => 'updated title',
        ]);

        $this->seeInDatabase('comments', [
            'title' => 'new title',
            'body'  => 'for new comment',
        ]);

        $this->seeInDatabase('authors', [
            'id'   => $author->id,
            'name' => 'new shared author',
        ]);
    }

    /**
     * @test
     * @expectedException \Czim\NestedModelUpdater\Exceptions\InvalidNestedDataException
     * @expectedExceptionMessageRegExp #['"]auth_1['"]#
     */
    function it_throws_an_exception_if_a_temporary_id_is_used_for_different_models()
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
     * @expectedException \Czim\NestedModelUpdater\Exceptions\InvalidNestedDataException
     * @expectedExceptionMessageRegExp #['"]auth_1['"]#
     */
    function it_throws_an_exception_if_a_no_data_is_defined_for_a_temporary_id()
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
     * @expectedException \Czim\NestedModelUpdater\Exceptions\InvalidNestedDataException
     * @expectedExceptionMessageRegExp #['"]auth_1['"]#
     */
    function it_throws_an_exception_if_a_create_data_for_a_temporary_id_contains_a_primary_key_value()
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
     * @expectedException \Czim\NestedModelUpdater\Exceptions\InvalidNestedDataException
     * @expectedExceptionMessageRegExp #['"]auth_1['"]#
     */
    function it_throws_an_exception_if_multiple_inconsistent_sets_of_create_data_for_a_temporary_id_are_defined()
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

}
