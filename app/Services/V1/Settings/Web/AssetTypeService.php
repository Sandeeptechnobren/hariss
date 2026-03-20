<?php

namespace App\Services\V1\Settings\Web;

use App\Models\AssetType;
use Illuminate\Support\Facades\DB;
use Exception;

class AssetTypeService
{
    /**
     * Create Asset Type
     */
    public function create(array $data)
    {
        DB::beginTransaction();

        try {
            $assetType = AssetType::create($data);

            DB::commit();

            return [
                'status'  => 'success',
                'code'    => 201,
                'message' => 'Asset type created successfully',
                'data'    => $assetType
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

    /**
     * List Asset Types
     */
    public function list(int $perPage = 50)
    {
        try {
            $result = AssetType::orderBy('id', 'desc')->paginate($perPage);

            return [
                'status' => 'success',
                'code'   => 200,
                'message' => 'Asset types fetched successfully',

                // Raw data (Controller will apply the Resource)
                'data'   => $result->items(),

                // Pagination block
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
