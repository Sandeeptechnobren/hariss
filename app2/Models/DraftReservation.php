<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $reservation_code The full generated code (e.g., 'CUS-20251004-000101').
 * @property string $record_type The prefix of the record type (e.g., 'CUS').
 * @property int $user_id The ID of the user who reserved the code.
 * @property \Illuminate\Support\Carbon $reserved_at
 * @property \Illuminate\Support\Carbon $expires_at The time the reservation should be cleaned up.
 */
class DraftReservation extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     * Corresponds to the 'draft_reservations' table created earlier.
     * @var string
     */
    protected $table = 'draft_reservations';

    /**
     * The attributes that are mass assignable.
     * @var array<int, string>
     */
    protected $fillable = [
        'reservation_code',
        'record_type',
        'user_id',
        'expires_at',
    ];

    /**
     * The attributes that should be cast to native types.
     * @var array<string, string>
     */
    protected $casts = [
        'reserved_at' => 'datetime',
        'expires_at' => 'datetime', // Cast the expiry column to a Carbon instance
    ];
}
