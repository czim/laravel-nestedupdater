<?php
namespace Czim\NestedModelUpdater\Test;

use Czim\NestedModelUpdater\NestedModelUpdaterServiceProvider;
use Czim\NestedModelUpdater\Test\Helpers\AlternativeUpdater;
use Czim\NestedModelUpdater\Test\Helpers\Models\Author;
use Czim\NestedModelUpdater\Test\Helpers\Models\Genre;
use Czim\NestedModelUpdater\Test\Helpers\Models\Post;
use Czim\NestedModelUpdater\Test\Helpers\Models\Comment;
use Czim\NestedModelUpdater\Test\Helpers\Models\Special;
use Czim\NestedModelUpdater\Test\Helpers\Models\Tag;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Database\Eloquent\Model;
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
                'tags'   => true,
            ],
            Post::class => [
                'comments' => true,
                'genre'    => true,
                'authors'  => [
                    'link-only' => true,
                ],
                'tags' => true,
                'exceptional_attribute_name' => [
                    'method' => 'someOtherRelationMethod',
                ],
                'comment_has_one' => true,
                'specials' => true,
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

        Schema::create('tags', function($table) {
            $table->increments('id');
            $table->integer('taggable_id')->unsigned()->nullable();
            $table->string('taggable_type', 255)->nullable();
            $table->string('name', 50);
            $table->timestamps();
        });

        Schema::create('specials', function($table) {
            $table->string('special', 20)->unique();
            $table->integer('post_id')->unsigned()->nullable();
            $table->string('name', 50);
            $table->timestamps();
            $table->primary(['special']);
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
        // do not use create() method to prevent trait nested update from being applied
        $post = new Post([
            'title' => $title,
            'body'  => $body,
        ]);

        $post->save();

        return $post;
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


    /**
     * @param Model|null $taggable
     * @param string     $name
     * @return Tag
     */
    protected function createTag(Model $taggable = null, $name = 'test tag')
    {
        $tag = new Tag([
            'name'  => $name,
        ]);

        if ($taggable) {
            $tag->taggable_id   = $taggable->getKey();
            $tag->taggable_type = get_class($taggable);
        }

        $tag->save();
        
        return $tag;
    }

    /**
     * @param string $key
     * @param string $name
     * @return Genre
     */
    protected function createSpecial($key, $name = 'testing special')
    {
        return Special::create([
            'special' => $key,
            'name'    => $name,
        ]);
    }

    /**
     * @param mixed  $messages
     * @param string $key
     * @param string $like
     * @param bool   $isRegex if true, $like is already a regex string
     */
    protected function assertHasValidationErrorLike($messages, $key, $like, $isRegex = false)
    {
        if ( ! ($messages instanceof MessageBag)) {
            $this->fail("Messages should be a MessageBag instance");
        }
        /** @var MessageBag $messages */
        if ( ! $messages->has($key)) {
            $this->fail("Messages does not contain key '{$key}'");
        }

        $regex = $isRegex ? $like : '#' . preg_quote($like) . '#i';

        $matched = array_filter($messages->get($key), function ($message) use ($regex) {
            return preg_match($regex, $message);
        });

        if ( ! count($matched)) {
            $this->fail("Messages does not contain error for key '{$key}' that matches '{$regex}'");
        }
    }

    /**
     * @param mixed  $messages
     * @param string $key
     * @param string $regex
     */
    protected function assertHasValidationErrorRegex($messages, $key, $regex)
    {
        $this->assertHasValidationErrorLike($messages, $key, $regex, true);
    }
}
