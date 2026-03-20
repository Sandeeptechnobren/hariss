<?php

namespace App\Services\V1\MasterServices\Mob;

use App\Models\Agent_Transaction\LoadHeader;
use App\Models\Agent_Transaction\LoadDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LoadService
{
   public function create(array $data)
{
    return DB::transaction(function () use ($data) {

        $headerOsa = $data['header_osa_code'] ?? $this->generateSequentialCode('header');

        $header = LoadHeader::create([
            'osa_code'     => $headerOsa,
            'warehouse_id' => $data['warehouse_id'],
            'route_id'     => $data['route_id'],
            'salesman_id'  => $data['salesman_id'],
            'is_confirmed' => $data['is_confirmed'] ?? 1,
        ]);
        $details = collect($data['details'])->map(function ($detail) use ($header) {
            $detailOsa = $detail['osa_code'] ?? $this->generateSequentialCode('detail');

            return LoadDetail::create([
                'header_id' => $header->id,
                'osa_code'  => $detailOsa,
                'item_id'   => $detail['item_id'],
                'uom'       => $detail['uom'],
                'qty'       => $detail['qty'],
                'price'     => $detail['price'],
            ]);
        });
        $header->setRelation('details', $details);

        return $header;
    });
}
    private function generateSequentialCode(string $type): string
    {
        $prefix = $type === 'header' ? 'SLH' : 'SLD';
        $model  = $type === 'header' ? LoadHeader::class : LoadDetail::class;

        $lastRecord = $model::where('osa_code', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastRecord && preg_match('/\d+$/', $lastRecord->osa_code, $matches)) {
            $nextNumber = (int)$matches[0] + 1;
        } else {
            $nextNumber = 1;
        }
        return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

 public function updateByUuid($uuid, array $data)
{
    $loadHeader = LoadHeader::where('uuid', $uuid)->firstOrFail();
    if (!empty($data['salesman_sign']) && $data['salesman_sign'] instanceof \Illuminate\Http\UploadedFile) {
        $now = now();
        $folderPath = "signature_images/{$now->format('Y')}/" . strtolower($now->format('F'));
        Storage::disk('public')->makeDirectory($folderPath);
        $filename = Str::random(10) . '.' . $data['salesman_sign']->getClientOriginalExtension();
        $path = $data['salesman_sign']->storeAs($folderPath, $filename, 'public');
        $data['salesman_sign'] = $path;
    } else {
        unset($data['salesman_sign']);
    }
    if (isset($data['longitude'])) {
        $data['longtitude'] = $data['longitude'];
        unset($data['longitude']);
    }
    $loadHeader->update([
        'salesman_sign' => $data['salesman_sign'] ?? $loadHeader->salesman_sign,
        'accept_time'   => $data['accept_time'] ?? $loadHeader->accept_time,
        'load_id'       => $data['load_id'] ?? $loadHeader->load_id,
        'latitude'      => $data['latitude'] ?? $loadHeader->latitude,
        'longtitude'    => $data['longtitude'] ?? $loadHeader->longtitude,
        'sync_time'     => $data['sync_time'] ?? now(),
        'is_confirmed'  => $data['is_confirmed'] ?? 1,
    ]);
    return $loadHeader;
}

 public function getLoadList($salesman_id)
    {
        $today = Carbon::today();

        return LoadHeader::where('salesman_id', $salesman_id)
                    ->whereDate('created_at', $today) 
                    ->orderBy('created_at', 'desc')
                    ->get();
    }
}