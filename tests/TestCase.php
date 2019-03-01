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
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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

        $app['config']->set('nestedmodelupdater.validation.model-rules-namespace', 'Czim\\NestedModelUpdater\\Test\\Helpers\\Rules');
        $app['config']->set('nestedmodelupdater.validation.model-rules-postfix', 'Rules');
        $app['config']->set('nestedmodelupdater.validation.allow-missing-rules', true);
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
            $table->string('unfillable', 20)->nullable();
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
     * Asserts that a given MessageBag contains a validation error for a key,
     * based on a loosy or regular expression match.
     *
     * @param mixed  $messages
     * @param string $key
     * @param string $like
     * @param bool   $isRegex if true, $like is already a regex string
     */
    protected function assertHasValidationErrorLike($messages, $key, $like, $isRegex = false)
    {
        if ( ! ($messages instanceof MessageBag)) {
            $this->fail("Messages should be a MessageBag instance, cannot look up presence of '{$like}' for '{$key}'.");
        }
        /** @var MessageBag $messages */
        if ( ! $messages->has($key)) {
            $this->fail("Messages does not contain key '{$key}' (cannot look up presence of '{$like}').");
        }

        $regex = $isRegex ? $like : '#' . preg_quote($like) . '#i';

        $matched = array_filter($messages->get($key), function ($message) use ($regex) {
            return preg_match($regex, $message);
        });

        if ( ! count($matched)) {
            $this->fail("Messages does not contain error for key '{$key}' that matches '{$regex}'.");
        }
    }

    /**
     * Asserts that a given MessageBag contains a validation error for a key,
     * based on a regular expression match.
     *
     * @param mixed  $messages
     * @param string $key
     * @param string $regex
     */
    protected function assertHasValidationErrorRegex($messages, $key, $regex)
    {
        $this->assertHasValidationErrorLike($messages, $key, $regex, true);
    }

    /**
     * Asserts whether a set of validation rules per key are present in an array
     * with validation rules.
     *
     * @param array|mixed $rules
     * @param array       $findRules    associative array with key => rules to find
     * @param bool        $strictPerKey if true, the rules for each key present should match strictly
     * @param bool        $strictKeys   if true, only the keys must be present in the rules, and no more
     */
    protected function assertHasValidationRules($rules, array $findRules, $strictPerKey = false, $strictKeys = false)
    {
        foreach ($findRules as $key => $findRule) {
            $this->assertHasValidationRule($rules, $key, $findRule, $strictPerKey);
        }

        if ($strictKeys) {
            if (count($rules) > count($findRules)) {
                $this->fail(
                    "Not strictly the same rules: "
                    . (count($rules) - count($findRules)) . ' more keys present than expected'
                    . ' (' . implode(', ', array_diff(array_keys($rules), array_keys($findRules))) . ').'
                );
            }
        }
    }

    /**
     * Asserts whether a given single validation rule is present in an array with
     * validation rules, for a given key. Does not care whether the format is
     * pipe-separate string or array.
     *
     * @param array|mixed  $rules
     * @param string       $key
     * @param string|array $findRules   full validation rule string ('max:50'), or array of them
     * @param bool         $strict      only the given rules should be present, no others
     */
    protected function assertHasValidationRule($rules, $key, $findRules, $strict = false)
    {
        if ( ! is_array($findRules)) {
            $findRules = [ $findRules ];
        }

        $this->assertIsArray($rules, "Rules should be an array, can not look up value '{$findRules[0]}' for '{$key}'.");

        $this->assertArrayHasKey($key, $rules, "Rules array does not contain key '{$key}' (cannot find rule '{$findRules[0]}').");

        $rulesForKey = $rules[ $key ];

        if ( ! is_array($rulesForKey)) {
            $rulesForKey = explode('|', $rulesForKey);
        }

        foreach ($findRules as $findRule) {
            $this->assertContains($findRule, $rulesForKey, "Rules array does not contain rule '{$findRule}' for key '{$key}'.");
        }

        if ($strict) {
            $this->assertLessThanOrEqual(
                count($rulesForKey),
                count($findRules),
                "Not strictly the same rules for '{$key}': "
                    . (count($rulesForKey) - count($findRules)) . ' more present than expected'
                    . ' (' . implode(', ', array_diff(array_values($rulesForKey), array_values($findRules))) . ').'
            );
        }
    }

}
