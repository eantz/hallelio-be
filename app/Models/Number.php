<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Number newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Number newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Number query()
 * @property int $id
 * @property int $num
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Number whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Number whereNum($value)
 * @mixin \Eloquent
 */
class Number extends Model
{
    protected $primaryKey = 'n';
    public $timestamps = false;
}
