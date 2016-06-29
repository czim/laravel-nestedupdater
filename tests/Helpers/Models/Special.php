<?php
namespace Czim\NestedModelUpdater\Test\Helpers\Models;

use Illuminate\Database\Eloquent\Model;

class Special extends Model
{
    protected $primaryKey = 'special';
    public $incrementing = false;

    protected $fillable = [ 'special', 'name' ];

    public function posts()
    {
        return $this->belongsTo(Post::class);
    }

}
