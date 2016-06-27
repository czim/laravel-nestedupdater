<?php
namespace Czim\NestedModelUpdater\Test;

use Czim\NestedModelUpdater\NestedModelUpdaterServiceProvider;
use Czim\NestedModelUpdater\Test\Helpers\AlternativeUpdater;
use Czim\NestedModelUpdater\Test\Helpers\Models\Author;
use Czim\NestedModelUpdater\Test\Helpers\Models\Genre;
use Czim\NestedModelUpdater\Test\Helpers\Models\Post;
use Czim\NestedModelUpdater\Test\Helpers\Models\Comment;
use Illuminate\Support\Facades\Schema;
use Mockery;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->migrateDatabase();
        $this->seedDatabase();
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app->register(NestedModelUpdaterServiceProvider::class);

        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Setup basic config for nested relation testing
        $app['config']->set('nestedmodelupdater.relations', [
            Author::class => [
                'posts'    => true,
                'comments' => [
                    'updater' => AlternativeUpdater::class,
                ],
            ],
            Comment::class => [
                // post left out deliberately
                'author' => true,
            ],
            Post::class => [
                'comments' => true,
                'genre' => true,
                'authors' => [
                    'link-only' => true,
                ],
                'exceptional_attribute_name' => [
                    'method' => 'someOtherRelationMethod',
                ],
                'comment_has_one' => true,
            ],
        ]);
    }

    protected function migrateDatabase()
    {
        Schema::create('genres', function($table) {
            $table->increments('id');
            $table->string('name', 50);
            $table->timestamps();
        });

        Schema::create('authors', function($table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->enum('gender', [ 'm', 'f' ])->default('f');
            $table->timestamps();
        });

        Schema::create('posts', function($table) {
            $table->increments('id');
            $table->integer('genre_id')->nullable()->unsigned();
            $table->string('title', 50);
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('comments', function($table) {
            $table->increments('id');
            $table->integer('post_id')->unsigned();
            $table->integer('author_id')->nullable()->unsigned();
            $table->string('title', 50);
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('author_post', function($table) {
            $table->increments('id');
            $table->integer('author_id')->unsigned();
            $table->integer('post_id')->unsigned();
        });
    }

    protected function seedDatabase()
    {
    }


    /**
     * @param string $name
     * @param string $gender
     * @return Author
     */
    protected function createAuthor($name = 'Test Author', $gender = 'm')
    {
        return Author::create([
            'name'   => $name,
            'gender' => $gender,
        ]);
    }

    /**
     * @param string $name
     * @return Genre
     */
    protected function createGenre($name = 'testing genre')
    {
        return Genre::create([
            'name' => $name,
        ]);
    }

    /**
     * @param string $title
     * @param string $body
     * @return Post
     */
    protected function createPost($title = 'testing title', $body = 'testing body')
    {
        return Post::create([
            'title' => $title,
            'body'  => $body,
        ]);
    }

    /**
     * @param Post   $post
     * @param string $title
     * @param string $body
     * @param null   $author
     * @return Comment
     */
    protected function createComment(Post $post, $title = 'testing title', $body = 'testing body', $author = null)
    {
        $comment = new Comment([
            'title' => $title,
            'body'  => $body,
        ]);

        if ($author) {
            $comment->author()->associate($author);
        }

        return $post->comments()->save($comment);
    }

}
