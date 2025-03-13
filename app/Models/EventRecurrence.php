<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property-read \App\Models\Event|null $event
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRecurrence newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRecurrence newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRecurrence query()
 * @property int $id
 * @property int $event_id
 * @property string $recurrence_type
 * @property string $start_date
 * @property string $end_date
 * @property int $interval
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRecurrence whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRecurrence whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRecurrence whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRecurrence whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRecurrence whereInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRecurrence whereRecurrenceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRecurrence whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventRecurrence whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EventRecurrence extends Model
{

    protected $fillable = [
        'event_id',
        'recurrence_type',
        'start_date',
        'end_date',
        'interval'
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
