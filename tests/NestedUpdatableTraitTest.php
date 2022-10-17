<?php

declare(strict_types=1);

namespace Czim\NestedModelUpdater\Test;

use Czim\NestedModelUpdater\Test\Helpers\Models\Author;
use Czim\NestedModelUpdater\Test\Helpers\Models\Post;

class NestedUpdatableTraitTest extends TestCase
{
    /**
     * @test
     */
    public function it_allows_a_model_to_be_created_without_any_nested_relations(): void
    {
        $data = [
            'title' => 'created',
            'body'  => 'fresh',
        ];

        $post = Post::create($data);

        static::assertInstanceOf(Post::class, $post);
        static::assertTrue($post->exists);

        $this->assertDatabaseHas('posts', [
            'id'    => $post->id,
            'title' => 'created',
            'body'  => 'fresh',
        ]);
    }

    /**
     * @test
     */
    public function it_allows_a_model_to_be_updated_without_any_nested_relations(): void
    {
        $post = $this->createPost();

        $data = [
            'title' => 'updated',
            'body'  => 'fresh',
        ];

        $result = $post->update($data);

        static::assertTrue($result, 'Update call should return boolean true');

        $this->assertDatabaseHas('posts', [
            'id'    => $post->id,
            'title' => 'updated',
            'body'  => 'fresh',
        ]);
    }

    /**
     * @test
     */
    public function it_creates_a_new_nested_model_related_as_belongs_to(): void
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

        static::assertInstanceOf(Post::class, $post);
        static::assertTrue($post->exists);

        $author = Author::latest()->first();
        static::assertInstanceOf(Author::class, $author, 'Author model should have been created');

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
    public function it_updates_an_existing_nested_model_related_as_belongs_to(): void
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

        static::assertTrue($result, 'Update call should return boolean true');

        $this->assertDatabaseHas('comments', [
            'id'      => $comment->id,
            'post_id' => $post->id,
            'title'   => 'updated comment',
        ]);
    }
}
