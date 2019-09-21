<?php
namespace Czim\NestedModelUpdater\Test\Helpers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Tag extends Model
{
    /**
     * @var array
     */
    protected $fillable = [ 'name' ];

    public function posts(): MorphTo
    {
        return $this->morphTo(Post::class, 'taggable');
    }

    public function comments(): MorphTo
    {
        return $this->morphTo(Comment::class, 'taggable');
    }

    // for testing per-model rules class/method validation configuration
    public function rules(): array
    {
        return [
            'name' => 'in:custom,tag,rules',
        ];
    }
}
