<?php
namespace Czim\NestedModelUpdater\Test\Helpers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string      $special
 * @property string|null $name
 */
class Special extends Model
{
    /**
     * @var string
     */
    protected $primaryKey = 'special';

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var array
     */
    protected $fillable = [ 'special', 'name' ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

}
