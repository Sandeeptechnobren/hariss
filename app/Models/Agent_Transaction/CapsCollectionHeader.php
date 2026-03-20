<?php

namespace App\Models\Agent_Transaction;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Blames;
use App\Models\Warehouse;
use App\Models\Route;
use App\Models\Salesman;
use App\Models\AgentCustomer;
use App\Models\Agent_Transaction\CapsCollectionDetail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CapsCollectionHeader extends Model
{
    use HasFactory,SoftDeletes,Blames;

    protected $table = 'caps_collection_headers';

    protected $fillable = [
        'code',
        'uuid',
        'warehouse_id',
        'route_id',
        'salesman_id',
        'customer',
        'status',
        'contact_no',
        'latitude',
        'longitude',
    ];

protected static function boot()
{
    parent::boot();
    static::creating(function ($model) {
        if (empty($model->uuid)) {
            $model->uuid = \Str::uuid()->toString();
        }

    });
}

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }

    public function salesman()
    {
        return $this->belongsTo(Salesman::class, 'salesman_id');
    }

    public function details()
    {
        return $this->hasMany(CapsCollectionDetail::class, 'header_id');
    }

        public function customerdata()
    {
        return $this->belongsTo(AgentCustomer::class, 'customer');
    }
}