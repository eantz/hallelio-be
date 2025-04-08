<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'event_occurence_id',
        'attendance_type',
        'member_id',
        'guest_name',
        'attended_at',
    ];

    public function eventOccurence(): BelongsTo
    {
        return $this->belongsTo(EventOccurence::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
