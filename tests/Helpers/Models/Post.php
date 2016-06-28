<?php
namespace Czim\NestedModelUpdater\Test\Helpers\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [ 'title', 'body' ];

    public function authors()
    {
        return $this->belongsToMany(Author::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function genre()
    {
        return $this->belongsTo(Genre::class);
    }

    public function tags()
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
    
}
