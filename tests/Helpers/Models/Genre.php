<?php
namespace Czim\NestedModelUpdater\Test\Helpers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int         $id
 * @property string|null $name
 */
class Genre extends Model
{
    use SoftDeletes;

    /**
     * @var array
     */
    protected $fillable = [ 'name' ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    // for testing the validator configuration
    public function customRulesMethod(): array
    {
        return [
            'name' => 'in:custom,rules,work',
        ];
    }

    public function brokenCustomRulesMethod(): string
    {
        return 'something other than an array';
    }
}
