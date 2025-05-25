<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Import BelongsTo

class Reminder extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * Allow these fields to be filled via create() or update() methods.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id', // Although often set automatically, good to have
        'title',
        'description',
        'reminder_time',
        'notify_minutes_before',
        'guest_emails',
        'google_event_id', // Needed later for GCal sync
    ];

    /**
     * The attributes that should be cast.
     * Automatically convert reminder_time to a Carbon datetime object.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'reminder_time' => 'datetime',
    ];

    /**
     * Get the user that owns the reminder.
     */
    public function user(): BelongsTo // Define return type
    {
        return $this->belongsTo(User::class);
    }
}