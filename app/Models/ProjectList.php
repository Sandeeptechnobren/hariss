<?php

namespace App\Models;
use App\Traits\Blames;
use App\Models\SalesmanType;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProjectList extends Model
{
    use HasFactory, Blames;

    protected $table = 'project_list';

    protected $fillable = [
        'uuid',
        'osa_code',
        'name',
        'salesman_type_id',
        'status',
        'created_user',
        'updated_user',
        'deleted_user',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            $project->uuid = Str::uuid();
            $project->osa_code = 'OSA' . str_pad(self::count() + 1, 5, '0', STR_PAD_LEFT);
        });
    }

    public function salesmanType()
    {
        return $this->belongsTo(SalesmanType::class, 'salesman_type_id');
    }
}
