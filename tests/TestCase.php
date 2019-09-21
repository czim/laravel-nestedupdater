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
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{

    public function setUp(): void
    {
        parent::setUp();

        $this->migrateDatabase();
        $this->seedDatabase();
    }

    /**
     * Define environment setup.
     *
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app): void
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

    protected function migrateDatabase(): void
    {
        Schema::create('genres', static function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50);
            $table->timestamps();
        });

        Schema::create('authors', static function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->enum('gender', [ 'm', 'f' ])->default('f');
            $table->timestamps();
        });

        Schema::create('posts', static function (Blueprint $table) {
            $table->increments('id');
            $table->integer('genre_id')->nullable()->unsigned();
            $table->string('title', 50);
            $table->text('body');
            $table->string('unfillable', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('comments', static function (Blueprint $table) {
            $table->increments('id');
            $table->integer('post_id')->unsigned();
            $table->integer('author_id')->nullable()->unsigned();
            $table->string('title', 50);
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('author_post', static function (Blueprint $table) {
            $table->increments('id');
            $table->integer('author_id')->unsigned();
            $table->integer('post_id')->unsigned();
        });

        Schema::create('tags', static function (Blueprint $table) {
            $table->increments('id');
            $table->integer('taggable_id')->unsigned()->nullable();
            $table->string('taggable_type', 255)->nullable();
            $table->string('name', 50);
            $table->timestamps();
        });

        Schema::create('specials', static function (Blueprint $table) {
            $table->string('special', 20)->unique();
            $table->integer('post_id')->unsigned()->nullable();
            $table->string('name', 50);
            $table->timestamps();
            $table->primary(['special']);
        });
    }

    protected function seedDatabase(): void
    {
    }


    protected function createAuthor(string $name = 'Test Author', string $gender = 'm'): Author
    {
        return Author::create([
            'name'   => $name,
            'gender' => $gender,
        ]);
    }

    protected function createGenre(string $name = 'testing genre'): Genre
    {
        return Genre::create([
            'name' => $name,
        ]);
    }

    protected function createPost(string $title = 'testing title', string $body = 'testing body'): Post
    {
        // do not use create() method to prevent trait nested update from being applied
        $post = new Post([
            'title' => $title,
            'body'  => $body,
        ]);

        $post->save();

        return $post;
    }

    protected function createComment(
        Post $post,
        string $title = 'testing title',
        string $body = 'testing body',
        ?Author $author = null
    ): Comment {

        $comment = new Comment([
            'title' => $title,
            'body'  => $body,
        ]);

        if ($author) {
            $comment->author()->associate($author);
        }

        return $post->comments()->save($comment);
    }

    protected function createTag(Model $taggable = null, string $name = 'test tag'): Tag
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

    protected function createSpecial(string $key, string $name = 'testing special'): Special
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
    protected function assertHasValidationErrorLike($messages, string $key, string $like, bool $isRegex = false): void
    {
        if ( ! ($messages instanceof MessageBag)) {
            $this->fail("Messages should be a MessageBag instance, cannot look up presence of '{$like}' for '{$key}'.");
        }
        /** @var MessageBag $messages */
        if ( ! $messages->has($key)) {
            $this->fail("Messages does not contain key '{$key}' (cannot look up presence of '{$like}').");
        }

        $regex = $isRegex ? $like : '#' . preg_quote($like, '#') . '#i';

        $matched = array_filter(
            $messages->get($key),
            static function ($message) use ($regex) {
                return preg_match($regex, $message);
            }
        );

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
    protected function assertHasValidationErrorRegex($messages, string $key, string $regex): void
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
    protected function assertHasValidationRules(
        $rules,
        array $findRules,
        bool $strictPerKey = false,
        bool $strictKeys = false
    ): void {

        foreach ($findRules as $key => $findRule) {
            $this->assertHasValidationRule($rules, $key, $findRule, $strictPerKey);
        }

        if ($strictKeys && count($rules) > count($findRules)) {

            $this->fail(
                'Not strictly the same rules: '
                . (count($rules) - count($findRules)) . ' more keys present than expected'
                . ' (' . implode(', ', array_diff(array_keys($rules), array_keys($findRules))) . ').'
            );
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
    protected function assertHasValidationRule($rules, string $key, $findRules, bool $strict = false): void
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
