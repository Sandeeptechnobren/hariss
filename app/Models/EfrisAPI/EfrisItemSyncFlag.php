<?php

namespace App\Models\EfrisAPI;

use Illuminate\Database\Eloquent\Model;

class EfrisItemSyncFlag extends Model
{
    protected $table = 'tbl_efris_item_sync_flags';

    protected $fillable = [
        'item_id',
        'warehouse_id',
        'is_synced'
    ];
}
