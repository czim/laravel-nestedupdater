<?php
namespace Czim\NestedModelUpdater\Test\Helpers\Models;

use Czim\NestedModelUpdater\Traits\NestedUpdatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int         $id
 * @property string|null $title
 * @property string|null $body
 */
class Post extends Model
{
    use NestedUpdatable;

    /**
     * @var array
     */
    protected $fillable = [ 'title', 'body' ];

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


    public function someOtherRelationMethod()
    {
        return $this->belongsTo(Genre::class);
    }

    public function commentHasOne()
    {
        return $this->hasOne(Comment::class);
    }

    public function specials()
    {
        return $this->hasMany(Special::class);
    }

    // for testing per-model rules class/method validation configuration
    public function customRulesMethod()
    {
        return [
            'title' => 'in:custom,post,rules',
        ];
    }

}
