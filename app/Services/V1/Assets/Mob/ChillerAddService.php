<?php

namespace App\Services\V1\Assets\Mob;

use App\Models\FrigeCustomerUpdate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Exception;

class ChillerAddService
{
public function create(array $data): FrigeCustomerUpdate
{
    DB::beginTransaction();

    try {

        $fileFields = [
            'national_id_file',
            'password_photo_file',
            'outlet_address_proof_file',
            'trading_licence_file',
            'lc_letter_file',
            'outlet_stamp_file',
            'sign__customer_file',
            'national_id1_file',
            'password_photo1_file',
            'outlet_address_proof1_file',
            'trading_licence1_file',
            'lc_letter1_file',
            'outlet_stamp1_file',
            'sign_salesman_file',
            'fridge_scan_img',
        ];

        foreach ($fileFields as $field) {
            if (isset($data[$field]) && $data[$field] instanceof UploadedFile) {
                $filename = time() . '_' . Str::random(8) . '.' . $data[$field]->getClientOriginalExtension();
                $path = $data[$field]->storeAs('customer_frige', $filename, 'public');
                $data[$field] = 'storage/' . $path;
            } else {
                unset($data[$field]);
            }
        }

        $asset = FrigeCustomerUpdate::create($data);
        DB::commit();
        $workflow = DB::table('htapp_workflow_assignments')
            ->where('process_type', 'Frige_Customer_Update')
            ->where('is_active', true)
            ->first();
        if ($workflow) {
            $new=app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
                ->startApproval([
                    'workflow_id'  => $workflow->workflow_id,
                    'process_type' => 'Frige_Customer_Update',
                    'process_id'   => $asset->id,
                ]);
        }
        return $asset;
    } catch (\Throwable $e) {
        DB::rollBack();
        \Log::error('FrigeCustomerUpdate create failed', [
            'error' => $e->getMessage(),
            'data'  => $data,
        ]);
        throw new \Exception($e->getMessage());
    }
}




}
