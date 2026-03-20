<?php

namespace App\Services\V1\Assets\Mob;

use App\Models\ChillerRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ChillerRequestService
{
public function getAll(int $perPage = 50, array $filters = [], bool $dropdown = false)
    {
        try {
            $user = auth()->user();
            if ($dropdown) {
                $query = ChillerRequest::select(['id', 'asset_code', 'asset_name'])
                    ->orderBy('asset_name', 'asc');
                foreach ($filters as $field => $value) {
                    if (empty($value)) continue;
                    if (in_array($field, ['asset_code', 'asset_name'])) {
                        $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                    } elseif ($field === 'warehouse_id') {
                        $query->where('warehouse_id', $value);
                    } else {
                        $query->where($field, $value);
                    }
                }
                return $query->get();
            }
            $query = ChillerRequest::with([
                'warehouse:id,warehouse_code,warehouse_name',
                'createdBy:id,name,username',
                'updatedBy:id,name,username'
            ]);
            // $query = \App\Helpers\DataAccessHelper::filterAssets($query, $user);
            // foreach ($filters as $field => $value) {
            //     if (empty($value)) continue;
            //     if (in_array($field, ['asset_code', 'asset_name'])) {
            //         $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
            //     } elseif ($field === 'warehouse_id') {
            //         $query->where('warehouse_id', $value);
            //     } else {
            //         $query->where($field, $value);
            //     }
            // }
            $query->orderBy('created_at', 'desc');
            $result = $query->paginate($perPage);
            // $result->getCollection()->transform(function ($item) {
            //     $workflowRequest = \DB::table('htapp_workflow_requests')
            //         ->where('process_type', 'Chiller_Request')
            //         ->where('process_id', $item->id)
            //         ->orderBy('id', 'DESC')
            //         ->first();
            //     $item->approval_status = $workflowRequest->status ?? 'NO_WORKFLOW';
            //     $item->workflow_request_uuid = $workflowRequest->uuid ?? null;
            //     return $item;
            // });
            return $result;
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch assets: " . $e->getMessage());
        }
    }
public function generateCode(): string
    {
        do {
            $last = ChillerRequest::withTrashed()->latest('id')->first();
            $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'CR' . str_pad($next, 3, '0', STR_PAD_LEFT);
        } while (ChillerRequest::withTrashed()->where('osa_code', $osa_code)->exists());
        return $osa_code;
    }
public function create(array $data): ChillerRequest
{
    DB::beginTransaction();

    try {
        $data = array_merge($data, [
            'uuid'     => $data['uuid'] ?? Str::uuid()->toString(),
        ]);

        $fileColumns = [
            'password_photo_file',
            'lc_letter_file',
            'trading_licence_file',
            'outlet_stamp_file',
            'outlet_address_proof_file',
            'sign__customer_file',
            'national_id_file',
            'national_id1_file'
        ];

        foreach ($fileColumns as $col) {
            if (!empty($data[$col]) && $data[$col] instanceof UploadedFile) {
                $filename = time() . '_' . $data[$col]->getClientOriginalName();
                $path = $data[$col]->storeAs('chillers', $filename, 'public');
                $data[$col] = Storage::url($path);
            }
        }

        $chiller = ChillerRequest::create($data);
        DB::commit();
        $workflow = DB::table('htapp_workflow_assignments')
                ->where('process_type', 'Chiller_Request')
                ->where('is_active', true)
                ->first();
            if ($workflow) {
                app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
                    ->startApproval([
                        "workflow_id"  => $workflow->workflow_id,
                        "process_type" => "Chiller_Request",
                        "process_id"   => $chiller->id
                    ]);
            }
        return $chiller;
    } catch (Throwable $e) {
        DB::rollBack();

        Log::error("Chiller Request creation failed", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'data'  => $data,
            'user'  => Auth::id(),
        ]);

        throw new \Exception("Something went wrong, please try again.", 0, $e);
    }
}

public function findByUuid(string $uuid): ?ChillerRequest
    {
        if (!Str::isUuid($uuid)) {
            throw new \Exception("Invalid UUID format: {$uuid}");
        }
        return ChillerRequest::where('uuid', $uuid)->first();
    }
public function updateByUuid(string $uuid, array $data): chillerRequest
    {
        $chiller = $this->findByUuid($uuid);
        if (!$chiller) {
            throw new \Exception("Chiller not found or invalid UUID: {$uuid}");
        }
        DB::beginTransaction();
        try {
            $fileColumns = [
                'password_photo_file',
                'lc_letter_file',
                'trading_licence_file',
                'outlet_stamp_file',
                'outlet_address_proof_file',
                'sign__customer_file',
                'national_id_file'
            ];
            foreach ($fileColumns as $col) {
                if (!empty($data[$col]) && $data[$col] instanceof \Illuminate\Http\UploadedFile) {
                    $filename = Str::random(40) . '.' . $data[$col]->getClientOriginalExtension();
                    $folder = "chiller_requests";
                    $relativePath = $folder . '/' . $filename;
                    $data[$col]->storeAs($folder, $filename, 'public');
                    $appUrl = rtrim(config('app.url'), '/');
                    $data[$col] = $appUrl . '/storage/app/public/' . $relativePath;
                }
            }
            $chiller->fill($data);
            $chiller->save();
            DB::commit();
            return $chiller;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Chiller update failed", [
                'error'   => $e->getMessage(),
                'uuid'    => $uuid,
                'payload' => $data,
            ]);
            throw new \Exception("Something went wrong, please try again.", 0, $e);
        }
    }
}
