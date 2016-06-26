<?php
namespace Czim\NestedModelUpdater\Test;

use Czim\NestedModelUpdater\Data\UpdateResult;
use Czim\NestedModelUpdater\ModelUpdater;
use Czim\NestedModelUpdater\Test\Helpers\Models\Post;

class BasicModelUpdaterTest extends TestCase
{
    
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
        $this->assertTrue($result->model()->exists, "Created model should exist");

        $this->seeInDatabase('posts', [
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

        $this->seeInDatabase('posts', [
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

        $this->seeInDatabase('posts', [
            'id'       => $post->id,
            'title'    => 'updated aswell',
        ]);

        $post = Post::find($post->id);

        $this->assertEquals(1, $post->genre_id, "New Genre should be associated with Post");

        $this->seeInDatabase('genres', [
            'name' => 'New Genre',
        ]);
    }

    /**
     * @test
     */
    function it_updates_a_new_nested_model_related_as_belongs_to_without_updating_parent()
    {
        $post = $this->createPost();
        $genre = $this->createGenre();
        $post->genre()->associate($genre);

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

        $this->seeInDatabase('posts', $originalPostData);

        $this->seeInDatabase('genres', [
            'id'   => $genre->id,
            'name' => 'updated',
        ]);
    }

    /**
     * @test
     * @expectedException \Czim\NestedModelUpdater\Exceptions\NestedModelNotFoundException
     * @expectedExceptionMessageRegExp #Czim\\NestedModelUpdater\\Test\\Helpers\\Models\\Post#
     */
    function it_throws_an_exception_if_it_cannot_find_the_top_level_model_by_id()
    {
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
     * @expectedException \Czim\NestedModelUpdater\Exceptions\NestedModelNotFoundException
     * @expectedExceptionMessageRegExp #Czim\\NestedModelUpdater\\Test\\Helpers\\Models\\Genre.*\(nesting: genre\)#
     */
    function it_throws_an_exception_with_nested_key_if_it_cannot_find_a_nested_model_by_id()
    {
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
    function it_creates_and_updates_a_nested_hasmany_relation()
    {
        $post = $this->createPost();
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

        $this->seeInDatabase('comments', [
            'id'    => $comment->id,
            'title' => 'updated title',
        ]);

        $this->seeInDatabase('comments', [
            'title' => 'new title',
            'body'  => 'new body',
        ]);
    }

    /**
     * @test
     */
    function it_creates_nested_structure_of_all_new_models()
    {
    }
    
    /**
     * @test
     */
    function it_creates_a_nested_structure_with_linked_models()
    {
    }
    
    
}
