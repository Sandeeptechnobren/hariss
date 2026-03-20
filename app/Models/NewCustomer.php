<?php

namespace App\Models\Agent_Transaction;

use App\Models\Area;
use App\Models\CustomerCategory;
use App\Models\CustomerSubCategory;
use App\Models\CustomerType;
use App\Models\OutletChannel;
use App\Models\AgentCustomer;
use App\Models\Region;     
use App\Models\Route;
use App\Models\User;
use App\Models\Warehouse;
use App\Traits\Blames;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class NewCustomer extends Model
{
    use HasFactory, SoftDeletes, Blames;

    protected $table = 'new_customer';

    protected $fillable = [
        'uuid',
        'name',
        'customer_type',
        'route_id',
        'is_whatsapp',
        'whatsapp_no',
        'contact_no',
        'buyertype',
        'street',
        'town',
        'landmark',
        'district',
        'payment_type',
        'creditday',
        'vat_no',
        'outlet_channel_id',
        'category_id',
        'subcategory_id',
        'longitude',
        'latitude',
        'status',
        'customer_id',
        'warehouse',
        'contact_no2',
        'credit_limit',
        'qr_code',
        'created_user',
        'updated_user',
        'owner_name',
        'reject_reason',
        'approval_status'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = $model->uuid ?? Str::uuid();

             $latest = static::latest('id')->first();
            $nextId = $latest ? $latest->id + 1 : 1;
            $model->osa_code = 'NC' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
        });
    }

    // Relationships
    public function customertype()
    {
        return $this->belongsTo(CustomerType::class, 'customer_type_id');
    }

        public function outlet_channel()
    {
        return $this->belongsTo(OutletChannel::class, 'outlet_channel_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(CustomerCategory::class, 'category_id');
    }
    public function subcategory()
    {
        return $this->belongsTo(CustomerSubCategory::class, 'subcategory_id');
    }
    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }
    public function agentCustomer()
    {
        return $this->belongsTo(AgentCustomer::class, 'customer_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_user');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_user');
    }
    public function getWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse');
    }
}
