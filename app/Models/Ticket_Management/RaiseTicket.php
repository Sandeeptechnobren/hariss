<?php

namespace App\Models\Ticket_Management;

use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class RaiseTicket extends Model
{
    use HasFactory, SoftDeletes;


    protected $table = 'tbl_raise_ticket';

    protected $fillable = [
        'uuid',
        'ticket_code',
        'title',
        'description',
        'attachment',
        'device_detail',
        'user_id',
        'role_id',
        'time_to_resolve',
        'status',
        'issue_type',
        'priority',
        'severity',
        'created_user',
        'updated_user',
        'customer',
        'companyCustomer',
        'salesman'
    ];

    protected $casts = [
        'time_to_resolve' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'user_id'   => 'integer',
        'priority'  => 'integer',
        'severity'  => 'integer', 
        'issue_type' => 'integer',
        'customer'   => 'integer',
        'companyCustomer' => 'integer',
        'salesman'  => 'integer',
    ];

    protected $dates = ['deleted_at'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    // 🔹 Relationships

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class, 'raise_ticket_id');
    }
    public function assignUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function createdUser()
    {
        return $this->belongsTo(User::class, 'created_user');
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class, 'ticket_id')
            ->latest();
    }
}
