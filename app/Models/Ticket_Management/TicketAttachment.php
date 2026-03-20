<?php

namespace App\Models\Ticket_Management;

use Illuminate\Database\Eloquent\Model;

class TicketAttachment extends Model
{
    protected $table = 'ticket_attachments';

    protected $fillable = [
        'raise_ticket_id',
        'file_path'
    ];

    public function ticket()
    {
        return $this->belongsTo(RaiseTicket::class, 'raise_ticket_id');
    }
}
