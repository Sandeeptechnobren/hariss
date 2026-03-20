<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames; 

class ComplaintFeedback extends Model
{
      use SoftDeletes, Blames;
    protected $table = 'complaint_feedbacks';

    protected $fillable = [
        'complaint_title',
        'item_id',
        'merchendiser_id',
        'type',
        'complaint',
        'uuid',
        'complaint_code',
        'image',
        'customer_id',
    ];

    protected $casts = [
        'image' => 'array',
      ];

    public function item()
{
    return $this->belongsTo(Item::class, 'item_id', 'id');
}

public function merchendiser()
    {
        return $this->belongsTo(Salesman::class, 'merchendiser_id', 'id');
    }
public function customer()
    {
        return $this->belongsTo(CompanyCustomer::class, 'customer_id', 'id');
    }
}