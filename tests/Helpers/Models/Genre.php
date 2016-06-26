<?php
namespace Czim\NestedModelUpdater\Test\Helpers\Models;

use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    protected $fillable = [ 'name' ];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
