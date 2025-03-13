<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Event> $exceptions
 * @property-read int|null $exceptions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EventRecurrence> $recurrences
 * @property-read int|null $recurrences_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event query()
 * @property int $id
 * @property string $event_type
 * @property string $title
 * @property string $description
 * @property string $location
 * @property string|null $start_time
 * @property string|null $end_time
 * @property int $is_recurring
 * @property int $is_exception
 * @property int $exception_event_id
 * @property int|null $exception_is_removed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereEventType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereExceptionEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereExceptionIsRemoved($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereIsException($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereIsRecurring($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Event extends Model
{

    protected $fillable = [
        'event_type',
        'title',
        'description',
        'location',
        'start_time',
        'end_time',
        'is_recurring',
        'is_exception',
        'exception_event_id',
        'exception_is_removed'
    ];

    public function recurrence(): HasOne
    {
        return $this->hasOne(EventRecurrence::class);
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(Event::class, 'exception_event_id');
    }
}
