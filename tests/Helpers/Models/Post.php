<?php

declare(strict_types=1);

namespace Czim\NestedModelUpdater\Test\Helpers\Models;

use Czim\NestedModelUpdater\Traits\NestedUpdatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int         $id
 * @property string|null $title
 * @property string|null $body
 */
class Post extends Model
{
    /**
     * @use NestedUpdatable<Post>
     */
    use NestedUpdatable;

    /**
     * @var string[]
     */
    protected $fillable = [
        'title',
        'body',
    ];

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function genre(): BelongsTo
    {
        return $this->belongsTo(Genre::class);
    }

    public function tags(): MorphMany
    {
        return $this->morphMany(Tag::class, 'taggable');
    }


    public function someOtherRelationMethod(): BelongsTo
    {
        return $this->belongsTo(Genre::class);
    }

    public function commentHasOne(): HasOne
    {
        return $this->hasOne(Comment::class);
    }

    public function specials(): HasMany
    {
        return $this->hasMany(Special::class);
    }

    /**
     * @return array<string, string>
     */
    public function customRulesMethod(): array
    {
        return [
            'title' => 'in:custom,post,rules',
        ];
    }
}
