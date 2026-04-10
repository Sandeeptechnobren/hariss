<?php
// app/Models/UraSyncLog.php

namespace App\Models\EfrisAPI;

use Illuminate\Database\Eloquent\Model;

class EfrisSyncLogs extends Model
{
    protected $table = 'tbl_efris_sync_logs';
    protected $fillable = [
        'item_id',
        'warehouse_id',
        'operation_type',
        'interface_code',
        'request_payload',
        'response_payload',
        'is_success',
        'error_message',
        'synced_at'
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'is_success' => 'boolean'
    ];
}
