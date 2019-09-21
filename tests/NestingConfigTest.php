<?php
/** @noinspection ReturnTypeCanBeDeclaredInspection */
/** @noinspection AccessModifierPresentedInspection */

namespace Czim\NestedModelUpdater\Test;

use Czim\NestedModelUpdater\Contracts\ModelUpdaterInterface;
use Czim\NestedModelUpdater\Contracts\NestedValidatorInterface;
use Czim\NestedModelUpdater\NestingConfig;
use Czim\NestedModelUpdater\Test\Helpers\AlternativeUpdater;
use Czim\NestedModelUpdater\Test\Helpers\Models\Author;
use Czim\NestedModelUpdater\Test\Helpers\Models\Genre;
use Czim\NestedModelUpdater\Test\Helpers\Models\Post;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NestingConfigTest extends TestCase
{

    /**
     * @test
     */
    function it_returns_whether_an_attribute_key_is_for_a_nested_relation()
    {
        $config = new NestingConfig();

        static::assertTrue($config->isKeyNestedRelation('genre', Post::class));
        static::assertFalse($config->isKeyNestedRelation('does_not_exist', Post::class));
    }

    /**
     * @test
     */
    function it_returns_relation_info_object()
    {
        $config = new NestingConfig();

        $info = $config->getRelationInfo('genre', Post::class);

        static::assertTrue($info->isBelongsTo(), 'genre should have belongsTo = true');
        static::assertTrue($info->isSingular(), 'genre should have singular = true');
        static::assertTrue($info->isUpdateAllowed(), 'genre should be allowed updates');
        static::assertNull($info->getDetachMissing(), 'genre should have detach missing null');
        static::assertFalse($info->isDeleteDetached(), 'genre should not delete detached');

        static::assertInstanceOf(Genre::class, $info->model());
        static::assertEquals('genre', $info->relationMethod());
        static::assertEquals(BelongsTo::class, $info->relationClass());
        static::assertEquals(ModelUpdaterInterface::class, $info->updater());

        static::assertEquals(NestedValidatorInterface::class, $info->validator());
        static::assertEquals(false, $info->rulesClass());
        static::assertEquals(null, $info->rulesMethod());
    }

    /**
     * @test
     */
    function it_returns_relation_info_object_for_exceptions()
    {
        $config = new NestingConfig();

        // check exception for updater
        $info = $config->getRelationInfo('comments', Author::class);
        static::assertEquals(AlternativeUpdater::class, $info->updater());

        // check exception for relation method
        $info = $config->getRelationInfo('exceptional_attribute_name', Post::class);
        static::assertEquals('someOtherRelationMethod', $info->relationMethod());

        // only allow links
        $info = $config->getRelationInfo('authors', Post::class);
        static::assertFalse($info->isUpdateAllowed());
    }

}
