<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventOccurence extends Model
{
    protected $fillable = [
        'event_id',
        'occurence_time',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
