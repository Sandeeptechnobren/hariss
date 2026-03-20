<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HtappWorkflow extends Model
{
    protected $table = 'htapp_workflows';

    protected $fillable = [
        'uuid', 
        'name',
        'description',
        'is_active'
    ];
}
