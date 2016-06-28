<?php
namespace Czim\NestedModelUpdater\Test\Helpers\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = [ 'title', 'body' ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function author()
    {
        return $this->belongsTo(Author::class);
    }

    public function tags()
    {
        return $this->morphMany(Tag::class, 'taggable');
    }
    
}
