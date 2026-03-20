<?php

namespace App\Models\Ticket_Management;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketComment extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_ticket_comments';

    protected $fillable = [
        'ticket_id',
        'comment',
        'created_user',
        'updated_user',
        'deleted_user'
    ];

    protected $casts = [
        'ticket_id' => 'integer',
        'created_user' => 'integer',
        'updated_user' => 'integer',
    ];

    /* -----------------------------
       🔗 Relationships
    ------------------------------*/

    public function ticket()
    {
        return $this->belongsTo(RaiseTicket::class, 'ticket_id');
    }

    public function createdUser()
    {
        return $this->belongsTo(User::class, 'created_user');
    }

    public function updatedUser()
    {
        return $this->belongsTo(User::class, 'updated_user');
    }
}
