<?php

declare(strict_types=1);

namespace Czim\NestedModelUpdater\Test\Helpers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int         $id
 * @property int         $taggable_id
 * @property string      $taggable_type
 * @property string|null $name
 */
class Tag extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
    ];

    public function posts(): MorphTo
    {
        return $this->morphTo(Post::class, 'taggable');
    }

    public function comments(): MorphTo
    {
        return $this->morphTo(Comment::class, 'taggable');
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'in:custom,tag,rules',
        ];
    }
}
