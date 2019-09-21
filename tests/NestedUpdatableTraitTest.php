<?php
/** @noinspection ReturnTypeCanBeDeclaredInspection */
/** @noinspection AccessModifierPresentedInspection */

namespace Czim\NestedModelUpdater\Test;

use Czim\NestedModelUpdater\Test\Helpers\Models\Author;
use Czim\NestedModelUpdater\Test\Helpers\Models\Post;

class NestedUpdatableTraitTest extends TestCase
{

    /**
     * @test
     */
    function it_allows_a_model_to_be_created_without_any_nested_relations()
    {
        $data = [
            'title' => 'created',
            'body'  => 'fresh',
        ];

        $post = Post::create($data);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertTrue($post->exists);

        $this->assertDatabaseHas('posts', [
            'id'    => $post->id,
            'title' => 'created',
            'body'  => 'fresh',
        ]);
    }

    /**
     * @test
     */
    function it_allows_a_model_to_be_updated_without_any_nested_relations()
    {
        $post = $this->createPost();

        $data = [
            'title' => 'updated',
            'body'  => 'fresh',
        ];

        $result = $post->update($data);

        $this->assertTrue($result, 'Update call should return boolean true');

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
        $data = [
            'title' => 'created',
            'body'  => 'fresh',
            'comments' => [
                [
                    'title' => 'created comment',
                    'body'  => 'comment body',
                    'author' => [
                        'name' => 'new author',
                    ]
                ]
            ],
        ];

        $post = Post::create($data);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertTrue($post->exists);

        $author = Author::latest()->first();
        $this->assertInstanceOf(Author::class, $author, 'Author model should have been created');

        $this->assertDatabaseHas('posts', [
            'id'    => $post->id,
            'title' => 'created',
            'body'  => 'fresh',
        ]);

        $this->assertDatabaseHas('comments', [
            'post_id'   => $post->id,
            'title'     => 'created comment',
            'body'      => 'comment body',
            'author_id' => $author->id,
        ]);

        $this->assertDatabaseHas('authors', [
            'id'   => $author->id,
            'name' => 'new author',
        ]);
    }

    /**
     * @test
     */
    function it_updates_an_existing_nested_model_related_as_belongs_to()
    {
        $post    = $this->createPost();
        $comment = $this->createComment($post);

        $data = [
            'comments' => [
                [
                    'id'    => $comment->id,
                    'title' => 'updated comment',
                ]
            ],
        ];

        $result = $post->update($data);

        $this->assertTrue($result, 'Update call should return boolean true');

        $this->assertDatabaseHas('comments', [
            'id'      => $comment->id,
            'post_id' => $post->id,
            'title'   => 'updated comment',
        ]);
    }

}
