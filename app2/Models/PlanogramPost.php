<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;

class PlanogramPost extends Model
{
    use HasFactory, SoftDeletes, Blames;
    protected $table = 'planogram_posts';

    protected $fillable = [
        'planogram_id',
        'merchendisher_id',
        'date',
        'customer_id',
        'shelf_id',
        'before_image',
        'after_image',
        'uuid',
    ];

        public function planogram()
    {
        return $this->belongsTo(Planogram::class, 'planogram_id');
    }

    public function merchendisher()
    {
        return $this->belongsTo(Salesman::class, 'merchendisher_id');
    }

    public function customer()
    {
        return $this->belongsTo(CompanyCustomer::class, 'customer_id');
    }

    public function shelf()
    {
        return $this->belongsTo(Shelve::class, 'shelf_id');
    }
}
