<?php

namespace App\Services\V1\Settings\Web;

use App\Models\AssetBranding;
use Illuminate\Support\Facades\DB;
use Exception;

class AssetBrandingService
{
    public function create(array $data)
    {
        DB::beginTransaction();

        try {
            $data['created_user'] = auth()->id();

            $branding = AssetBranding::create($data);

            DB::commit();

            return [
                'status'  => 'success',
                'code'    => 201,
                'message' => 'Branding created successfully',
                'data'    => $branding
            ];
        } catch (Exception $e) {

            DB::rollBack();
            return [
                'status'  => 'error',
                'code'    => 500,
                'message' => $e->getMessage()
            ];
        }
    }

    public function list(int $perPage = 50)
    {
        try {
            $result = AssetBranding::orderBy('id', 'desc')->paginate($perPage);

            return [
                'status' => 'success',
                'code'   => 200,
                'message' => 'Branding list fetched successfully',
                'data'   => $result->items(),
                'pagination' => [
                    'page'         => $result->currentPage(),
                    'limit'        => $result->perPage(),
                    'totalPages'   => $result->lastPage(),
                    'totalRecords' => $result->total(),
                ]
            ];
        } catch (Exception $e) {

            return [
                'status'  => 'error',
                'code'    => 500,
                'message' => $e->getMessage()
            ];
        }
    }
}
