<?php
namespace Czim\NestedModelUpdater\Test\Helpers\Models;

use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    protected $fillable = [ 'name', 'gender' ];

    public function posts()
    {
        return $this->belongsToMany(Post::class);
    }

    public function comments()
    {
        $this->hasMany(Comment::class);
    }
}
