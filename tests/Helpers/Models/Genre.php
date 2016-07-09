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

    // for testing the validator configuration
    public function customRulesMethod()
    {
        return [
            'name' => 'in:custom,rules,work',
        ];
    }

    public function brokenCustomRulesMethod()
    {
        return 'something other than an array';
    }
}
